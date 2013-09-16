<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @todo finish moving all content-mofication-actions to the model
 * @todo decide how much typecast we do on parameters passed to calls of model's methods
 */

class eZContentStagingRestLocationController extends eZContentStagingRestBaseController
{

    // *** rest actions ***

    /**
     * Handle GET on a location from its [remote] id
     *
     * Requests:
     * - GET /content/locations/remote/<remoteId>
     * - GET /content/locations/<Id>
     *
     * @return ezpRestMvcResult
     */
    public function doLoad()
    {

        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->attribute( 'can_read' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingLocation( $node );
        return $result;

    }

    /**
     * Handle hide or unhide request for a location from its [remote] id
     *
     * Requests:
     * - POST /content/locations/remote/<remoteId>?hide=<status>
     * - POST /content/locations/<Id>?hide=<status>
     *
     * @return ezpRestMvcResult
     */
    public function doHideUnhide()
    {
        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->attribute( 'can_hide' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        if ( !isset( $this->request->get['hide'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "hide" parameter is missing' );
        }

        /// @todo add perms checking

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingLocation::updateVisibility( $node, ( $this->request->get['hide'] == 'true' ) );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Update the sort order and sort field or the priority of a node
     *
     * Request:
     * - PUT /content/locations/remote/<remoteId>
     * - PUT /content/locations/<Id>
     * @return ezpRestMvcResult
     */
    public function doUpdate()
    {
        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->attribute( 'can_edit' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $modified = false;

        $inputVariables = $this->getRequestVariables();
        if ( isset( $inputVariables['sortField'] )
                && isset( $inputVariables['sortOrder'] ) )
        {
            eZContentStagingLocation::updateSort(
                $node,
                $inputVariables['sortField'],
                $inputVariables['sortOrder']
            );
            // we have to reload the node to pick up the change
            $modified = true;
        }

        if ( isset( $inputVariables['priority'] ) )
        {
            eZContentStagingLocation::updatePriority(
                $node,
                (int)$inputVariables['priority'] );
            // we have to reload the node to pick up the change
            $modified = true;
        }

        if ( isset( $inputVariables['remoteId'] ) )
        {
            if ( ( $result = eZContentStagingLocation::updateRemoteId(
                    $node,
                    $inputVariables['remoteId'] )
                ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
            $modified = true;
        }

        if ( isset( $inputVariables['mainLocationRemoteId'] ) )
        {
            $newMainLocation = eZContentObjectTreeNode::fetchByRemoteID( $inputVariables['mainLocationRemoteId'] );
            if ( !$newMainLocation instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Location with remote id '{$inputVariables['mainLocationRemoteId']}' not found" );
            }
            /// @todo check if new main location is same as current
            eZContentStagingLocation::updateMainLocation(
                $node,
                $newMainLocation );
            // we have to reload the node to pick up the change
            $modified = true;
        }

        if ( $modified )
        {
            $node = eZContentObjectTreeNode::fetch( $node->attribute( 'node_id' ) );
        }

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingLocation( $node );
        return $result;
    }

    /**
     * Handle move operation of a location to another location
     *
     * Request:
     * - PUT /content/locations/remote/<remoteId>/parent?destParentRemoteId=<dest>
     * - PUT /content/locations/<Id>/parent?destParentRemoteId=<dest>
     *
     * @return ezpRestMvcResult
     *
     * @todo add support for parentId also besides parentRemoteId
     */
    public function doMove()
    {
        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->attribute( 'can_move' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        if ( !isset( $this->request->get['destParentRemoteId'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "destParentRemoteId" parameter is missing' );
        }
        $destParentRemoteId = $this->request->get['destParentRemoteId'];

        $dest = eZContentObjectTreeNode::fetchByRemoteID( $destParentRemoteId );
        if ( !$dest instanceof eZContentObjectTreeNode )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Cannot find the location with remote id '{$destParentRemoteId}'" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingLocation::move( $node, $dest );

        $result = new ezpRestMvcResult();
        //$newNode = eZContentObjectTreeNode::fetch( $node->attribute( 'node_id' ) );
        //$result->variables = (array) new eZcontentStagingLocation( $newNode );
        return $result;

    }

    /**
     * Handle DELETE request for a location
     *
     * Request:
     * - DELETE /content/locations/remote/<remoteId>?trash=true|false
     * - DELETE /content/locations/<Id>?trash=true|false
     *
     * @return ezpRestMvcResult
     */
    public function doRemove()
    {
        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->attribute( 'can_remove' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $moveToTrash = true;
        if ( isset( $this->request->get['trash'] ) )
        {
            /// @todo move to common code: trim, strtolower
            $moveToTrash = ( $this->request->get['trash'] !== 'false' );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingLocation::remove( $node, $moveToTrash );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle swap operation for two locations
     *
     * Request:
     * - SWAP /content/locations/remote/<remoteId>?withRemoteId=<remoteId>
     * - SWAP /content/locations/<Id>?withRemoteId=<remoteId>
     *
     * @return ezpRestMvcResult
     */
    public function doSwap()
    {
        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
        }

        if ( !$node->canSwap() )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        if ( !isset( $this->request->get['withRemoteId'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "withRemoteId" parameter is missing' );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $node2 = eZContentObjectTreeNode::fetchByRemoteID( $this->request->get['withRemoteId'] );
        if ( !$node2 instanceof eZContentObjectTreeNode )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Location with remote id '" . $this->request->get['withRemoteId'] . "' not found" );
        }
        eZContentStagingLocation::swap( $node, $node2 );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    // *** helper methods ***

    /// @todo assert error if neither Id nor remoteId are present
    protected function node()
    {
        if ( isset( $this->remoteId ) )
        {
            $node = eZContentObjectTreeNode::fetchByRemoteID( $this->remoteId );
            if ( !$node instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Location with remote id '{$this->remoteId}' not found" );
            }
            return $node;
        }
        if ( isset( $this->Id ) )
        {
            $node = eZContentObjectTreeNode::fetch( $this->Id );
            if ( !$node instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Location with id '{$this->Id}' not found" );
            }
        }

        if ( !$node->attribute( 'can_read' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied for content with location '{$this->Id}' for user " . eZUser::currentUser()->attribute( 'login' ) );
        }

        return $node;
    }

}
