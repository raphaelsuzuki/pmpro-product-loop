<?php

namespace PMProProductLoop\Lifecycle;

use PMProProductLoop\Repository\Subscription_Repository;
use PMProProductLoop\Stock\Stock_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lifecycle {

	/** @var Subscription_Repository */
	private $repository;

	/** @var Stock_Manager */
	private $stock_manager;

	public function __construct( Subscription_Repository $repository, Stock_Manager $stock_manager ) {
		$this->repository    = $repository;
		$this->stock_manager = $stock_manager;
	}

	public function register_hooks(): void {
		add_action( 'pmpro_after_change_membership_level', array( $this, 'on_membership_change' ), 10, 2 );
	}

	public function on_membership_change( $level_id, $user_id ): void {
		$level_id = (int) $level_id;
		$user_id  = (int) $user_id;

		if ( $level_id <= 0 || $user_id <= 0 ) {
			return;
		}

		$subscription = $this->repository->find_active_by_user_and_level( $user_id, $level_id );
		if ( ! is_array( $subscription ) ) {
			return;
		}

		$this->repository->update_status( (int) $subscription['id'], 'cancelled' );
		$this->stock_manager->increment( (int) $subscription['product_id'] );

		do_action(
			'pmpro_pl_event',
			array(
				'type'     => 'lifecycle_cancelled',
				'hash'     => (string) $subscription['hash'],
				'level_id' => $level_id,
			)
		);
	}
}
