<?php

namespace WPLab\Ebay\Models;
class EbayResponsiblePerson extends EbayAddress {
	protected int $id = 0;
	protected string $date_added = '';

	public function __construct( $id = null ) {
		if ( $id ) {
			$this->populate( $id );
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	public function getDateAdded() {
		return $this->date_added;
	}

	public function setDateAdded( $date ) {
		$this->date_added = $date;
		return $this;
	}

	/**
	 * Creates a new Manufacturer record. Returns the new ID created or false on error
	 * @return int|bool
	 */
	public function save() {
		global $wpdb;

		$data = $this->toArray();

		if ( !empty( $data['id'] ) ) {
			// update existing
			return $this->update();
		}

		$data['date_added'] = current_time('mysql');
		unset($data['id']);

		if ( $wpdb->insert( $wpdb->prefix .'ebay_responsible_persons', $data ) ) {
			return $wpdb->insert_id;
		}

		// something went wrong
		WPLE()->logger->error( 'Error saving person. '. $wpdb->last_error );
		WPLE()->logger->debug( print_r( $data, 1 ) );
		return false;
	}

	/**
	 * Updates an existing Manufacturer
	 * @return bool
	 */
	public function update() {
		global $wpdb;

		$where  = [ 'id' => $this->getId() ];
		$data   = $this->toArray();

		unset( $data['id'], $data['date_added'] );

		if ( $wpdb->update( $wpdb->prefix .'ebay_responsible_persons', $data, $where ) ) {
			return true;
		}

		// something went wrong
		WPLE()->logger->error( 'Error updating person #'. $this->getId(). ': '. $wpdb->last_error );
		WPLE()->logger->debug( print_r( $data, 1 ) );
		return false;
	}

	/**
	 * Deletes a Manufacturer
	 * @return void
	 */
	public function delete() {
		global $wpdb;

		return $wpdb->delete( $wpdb->prefix .'ebay_responsible_persons',  ['id' => $this->getId()] );
	}

	protected function toArray() {
		$data = parent::toArray();
		$data['id'] = $this->getId();
		$data['date_added'] = $this->getDateAdded();
		return $data;
	}

	/**
	 * @param int $id
	 * @return object
	 */
	protected function load( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ebay_responsible_persons WHERE id = %d", $id ) );
	}
	private function populate( $id ) {
		$row = $this->load( $id );

		if ( $row ) {
			$this
				->setId( $id )
				->setCompany( $row->company )
				->setStreet1( $row->street1 )
				->setStreet2( $row->street2 )
				->setCity( $row->city )
				->setState( $row->state )
				->setPostcode( $row->postcode )
				->setCountry( $row->country )
				->setEmail( $row->email )
				->setPhone( $row->phone )
				->setDateAdded( $row->date_added );
		}
	}
}