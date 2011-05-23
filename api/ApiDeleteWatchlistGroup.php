<?php

/**
 * API module to delete semantic watchlist groups.
 *
 * @since 0.1
 *
 * @file ApiDeleteWatchlistGroup.php
 * @ingroup SemanticWatchlist
 * @ingroup API
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiDeleteWatchlistGroup extends ApiBase {
	
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}
	
	public function execute() {
		global $wgUser;
		
		if ( !$wgUser->isAllowed( 'semanticwatchgroups' ) || $wgUser->isBlocked() ) {
			$this->dieUsageMsg( array( 'badaccess-groups' ) );
		}
		
		$params = $this->extractRequestParams();
		
		$everythingOk = true;
		
		foreach ( $params['ids'] as $id ) {
			$everythingOk = $this->deleteGroup( $id ) && $everythingOk;
		}
		
		$this->getResult()->addValue(
			null,
			'success',
			$everythingOk
		);
	}
	
	/**
	 * Delete the group with specified ID, and
	 * all linked data not used by other groups.
	 * 
	 * @since 0.1
	 * 
	 * @param integer $groupId
	 * 
	 * @return boolean Sucess indicator
	 */
	protected function deleteGroup( $groupId ) {
		$everythingOk = true;
		
		$dbr = wfGetDB( DB_SLAVE );
		
		// Find all edits linked to this group.
		$editsForGroup = $dbr->select(
			array( 'swl_sets_per_group', 'swl_sets_per_edit', 'swl_edits' ),
			array( 'spe_edit_id' ),
			array(
				'spg_group_id' => $id,
			),
			'',
			array(),
			array(
				'swl_sets_per_edit' => array( 'INNER JOIN', array( 'spe_set_id=spg_set_id' ) ),
			)
		);
		
		$editsToDelete = array();
		
		// For each linked edit, find all linked groups, and save those with only one (this one).
		foreach ( $editsForGroup as $edit ) {
			$groupsForEdit = $dbr->select(
				array( 'swl_sets_per_group', 'swl_sets_per_edit', 'swl_groups' ),
				array( 'spg_group_id' ),
				array(
					'spe_edit_id' => $edit->edit_id,
				),
				'',
				array(),
				array(
					'swl_sets_per_edit' => array( 'INNER JOIN', array( 'spe_set_id=spg_set_id' ) ),
				)
			);
			
			if ( $dbr->numRows( $groupsForEdit ) < 2 ) {
				$editsToDelete[] = $edit->edit_id;
			}
		}
		
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin();
		
		// Delete all edits and sets per edits only linked to this group.
		foreach ( $editsToDelete as $editId ) {
			$dbw->delete(
				'swl_edits',
				array( 'edit_id' => $editId )
			);
			
			$dbw->delete(
				'swl_sets_per_edit',
				array( 'spe_edit_id' => $editId )
			);
		}
		
		// Delete sets per group links for this group. 
		$result = $dbw->delete(
			'swl_sets_per_group',
			array( 'spg_group_id' => $id )
		);

		if ( $result === false ) {
			$everythingOk = false;
		}
		
		// Delete users per group links for this group.
		$result = $dbw->delete(
			'swl_users_per_group',
			array( 'upg_group_id' => $id )
		);

		if ( $result === false ) {
			$everythingOk = false;
		}
		
		// Delete the actual group.
		$result = $dbw->delete(
			'swl_groups',
			array( 'group_id' => $id )
		);

		if ( $result === false ) {
			$everythingOk = false;
		}
		
		$dbw->commit();

		return $everythingOk; 
	}

	public function getAllowedParams() {
		return array(
			'ids' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}
	
	public function getParamDescription() {
		return array(
			'ids' => 'The IDs of the watchlist groups to delete'
		);
	}
	
	public function getDescription() {
		return array(
			'API module to delete semantic watchlist groups.'
		);
	}
		
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'missingparam', 'ids' ),
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=deleteswlgroup&ids=42|34',
		);
	}	
	
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}		
	
}