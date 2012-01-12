<?php
/**
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
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
     * @return void
     */
    public function doLoad()
    {

        $node = $this->node();
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            return $node;
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

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $modified = false;

        if ( isset( $this->request->inputVariables['sortField'] )
                && isset( $this->request->inputVariables['sortOrder'] ) )
        {
            if ( ( $result = eZContentStagingLocation::updateSort(
                      $node,
                      $this->request->inputVariables['sortField'],
                      $this->request->inputVariables['sortOrder']
                      )
                 ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
        }

        if ( isset( $this->request->inputVariables['priority'] ) )
        {
            if ( ( $result = eZContentStagingLocation::updatePriority(
                    $node,
                    (int)$this->request->inputVariables['priority'] )
                ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
        }

        if ( isset( $this->request->inputVariables['remoteId'] ) )
        {
            if ( ( $result = eZContentStagingLocation::updateRemoteId(
                    $node,
                    $this->request->inputVariables['remoteId'] )
                ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
        }

        if ( isset( $this->request->inputVariables['mainLocationRemoteId'] ) )
        {
            $newMainLocation = eZContentObjectTreeNode::fetchByRemoteID( $this->request->inputVariables['mainLocationRemoteId'] );
            if ( !$newMainLocation instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Location with remote id '{$this->request->inputVariables['mainLocationRemoteId']}' not found" );
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
        return $node;
    }

}