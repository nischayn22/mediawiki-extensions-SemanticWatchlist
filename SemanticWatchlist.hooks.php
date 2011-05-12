<?php

/**
 * Static class for hooks handled by the Semantic Watchlist extension.
 * 
 * @since 0.1
 * 
 * @file SemanticWatchlist.hooks.php
 * @ingroup SemanticWatchlist
 * 
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class SWLHooks {

    /**
     * Handle the onDataChanged hook of SMW >1.6, which gets called
     * every time the value of a propery changes somewhere.
     *
     * @since 0.1
     *
     * @param SMWStore $store
     * @param SMWChangeSet $changes
     * 
     * @return true
     */
	public static function onDataChanged( SMWStore $store, SMWChangeSet $changes ) {
		$title = Title::makeTitle( $changes->getSubject()->getNamespace(), $changes->getSubject()->getDBkey() );
		
        foreach ( SWLGroups::getMatchingWatchGroups( $title ) as /* SWLGroup */ $group ) {
        	var_dump($group);exit;
        	$group->notifyWatchingUsers( $changes );
    	}

		return true;
	}

    /**
     * Determines and returns if the specified watchlist group covers
     * the provided page or not. 
     * 
     * @since 0.1
     *
     * @param SWLGroup $group
     * @param array $userIDs
     * @param SMWChangeSet $changes
     *
     * @return true
     */    
    public static function onGroupNotify( SWLGroup $group, array $userIDs, SMWChangeSet $changes ) {
        
    	foreach ( $userIDs as $userID ) {
    		self::notifyUser( $group, User::newFromId( $userID ), $changes );
    	}
    	
        return true;
    }
    
    protected static function notifyUser( SWLGroup $group, User $user, SMWChangeSet $changes ) {
    	var_dump($group);var_dump($user);var_dump($changes);exit;
    }

	/**
	 * Schema update to set up the needed database tables.
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return true
	 */
	public static function onSchemaUpdate( /* DatabaseUpdater */ $updater = null ) {
		global $wgDBtype;

		if ( $wgDBtype == 'mysql' ) {
            $updater->addExtensionUpdate( array(
                'addTable',
                'swl_groups',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
            $updater->addExtensionUpdate( array(
                'addTable',
                'swl_changes',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
			$updater->addExtensionUpdate( array(
                'addTable',
                'swl_changes_per_group',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
			$updater->addExtensionUpdate( array(
                'addTable',
                'swl_users_per_group',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
		}

		return true;
	}
	
}