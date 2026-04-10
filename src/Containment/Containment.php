<?php

namespace PMProProductLoop\Containment;

use PMProProductLoop\Intent\Subscription_Intent;
use PMProProductLoop\Repository\Subscription_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Containment {

	/** @var Subscription_Repository */
	private $repository;

	/** @var object|null */
	private $stock_manager;

	public function __construct( Subscription_Repository $repository, $stock_manager = null ) {
		$this->repository    = $repository;
		$this->stock_manager = $stock_manager;
	}

	/**
	 * Execute PMPro provisioning for the resolved intent.
	 *
	 * @return array|false
	 */
	public function subscribe( Subscription_Intent $intent ) {
		if ( ! $intent->is_valid() ) {
			return false;
		}

		$config = array(
			'name'           => 'Product Loop - Product ' . $intent->product_id,
			'billing_amount' => $intent->price,
			'cycle_number'   => 1,
			'cycle_period'   => $intent->period,
			'billing_limit'  => $intent->cycles,
		);

		$result = $this->pmpro_pl_execute_magic_level( $config );
		if ( ! is_array( $result ) || empty( $result['success'] ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Find an existing persisted subscription.
	 *
	 * @return array|null
	 */
	public function find_subscription( int $user_id, string $hash ): ?array {
		return $this->repository->find( $user_id, $hash );
	}

	/**
	 * Cancel a subscription by user and hash.
	 */
	public function cancel( int $user_id, string $hash ): bool {
		$subscription = $this->repository->find( $user_id, $hash );
		if ( ! $subscription ) {
			return false;
		}

		if ( 'active' !== $subscription['status'] ) {
			return true;
		}

		$level_id = (int) $subscription['level_id'];
		if ( function_exists( 'pmpro_changeMembershipLevel' ) ) {
			$cancelled = pmpro_changeMembershipLevel( 0, $user_id );
			if ( false === $cancelled ) {
				return false;
			}
		} else {
			return false;
		}

		$updated = $this->repository->update_status( (int) $subscription['id'], 'cancelled' );
		if ( ! $updated ) {
			return false;
		}

		if ( $this->stock_manager && method_exists( $this->stock_manager, 'increment' ) ) {
			$this->stock_manager->increment( (int) $subscription['product_id'] );
		}

		do_action(
			'pmpro_pl_event',
			array(
				'type'     => 'subscription_cancelled',
				'hash'     => (string) $subscription['hash'],
				'level_id' => $level_id,
			)
		);

		return true;
	}

	/**
	 * Execution-only wrapper around PMPro Magic Levels.
	 */
	private function pmpro_pl_execute_magic_level( array $config ) {
		return pmpro_magic_levels_process( $config );
	}
}
