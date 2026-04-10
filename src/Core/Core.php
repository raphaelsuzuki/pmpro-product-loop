<?php

namespace PMProProductLoop\Core;

use PMProProductLoop\Containment\Containment;
use PMProProductLoop\Intent\Subscription_Intent;
use PMProProductLoop\Repository\Subscription_Repository;
use PMProProductLoop\Stock\Stock_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {

	/** @var Subscription_Repository */
	private $repository;

	/** @var Containment */
	private $containment;

	/** @var Stock_Manager */
	private $stock_manager;

	public function __construct( Subscription_Repository $repository, Containment $containment, Stock_Manager $stock_manager ) {
		$this->repository    = $repository;
		$this->containment   = $containment;
		$this->stock_manager = $stock_manager;
	}

	/**
	 * Run full subscription orchestration without throwing exceptions.
	 *
	 * @return array{status:string,code:string,message:string,data?:array}
	 */
	public function process( Subscription_Intent $intent ): array {
		if ( ! $intent->is_valid() ) {
			$this->event( 'invalid_intent', (string) $intent->hash, 0 );
			return $this->result( 'error', 'invalid_intent', 'Subscription intent is invalid.' );
		}

		$existing = $this->repository->find( $intent->user_id, $intent->hash );
		if ( is_array( $existing ) ) {
			$this->event( 'idempotent_hit', $intent->hash, (int) ( $existing['level_id'] ?? 0 ) );
			return $this->result( 'ok', 'existing_subscription', 'Existing subscription found.', $existing );
		}

		if ( ! $this->stock_manager->decrement( $intent->product_id ) ) {
			$this->event( 'stock_exhausted', $intent->hash, 0 );
			return $this->result( 'error', 'stock_exhausted', 'Product is out of stock.' );
		}

		$execution = $this->containment->subscribe( $intent );
		if ( ! is_array( $execution ) || empty( $execution['success'] ) ) {
			$this->stock_manager->increment( $intent->product_id );
			$this->event( 'execution_failed', $intent->hash, 0 );
			return $this->result( 'error', 'execution_failed', 'Subscription execution failed.' );
		}

		$level_id = isset( $execution['level_id'] ) ? (int) $execution['level_id'] : 0;

		$row = $this->repository->find_or_create(
			array(
				'user_id'    => $intent->user_id,
				'hash'       => $intent->hash,
				'product_id' => $intent->product_id,
				'level_id'   => $level_id,
				'status'     => 'active',
				'created_at' => current_time( 'mysql' ),
			)
		);

		$this->event( 'subscription_persisted', $intent->hash, $level_id );

		return $this->result( 'ok', 'created', 'Subscription created.', $row );
	}

	/**
	 * @return array{status:string,code:string,message:string,data?:array}
	 */
	private function result( string $status, string $code, string $message, array $data = null ): array {
		$result = array(
			'status'  => $status,
			'code'    => $code,
			'message' => $message,
		);

		if ( is_array( $data ) ) {
			$result['data'] = $data;
		}

		return $result;
	}

	private function event( string $type, string $hash, int $level_id ): void {
		do_action(
			'pmpro_pl_event',
			array(
				'type'     => $type,
				'hash'     => $hash,
				'level_id' => $level_id,
			)
		);
	}
}
