<?php


class contentStagingRestContentController extends ezpRestMvcController
{

    /**
     * Handle DELETE request for a content object from its remote id
     *
     * Request:
     * - DELETE /api/contentstaging/content/objects/:remoteId[?trash=0|1]
     *
     * @return ezpRestMvcResult
     */
    public function doRemove()
    {
        $moveToTrash = true;
        if ( isset( $this->request->get['trash'] ) )
        {
            $moveToTrash = (bool)$this->request->get['trash'];
        }

        $result = new ezpRestMvcResult();

        $object = eZContentObject::fetchByRemoteID( $this->remoteId );
        if ( !$object instanceof eZContentObject )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Content with remote id '{$this->remoteId}' not found"
            );
            return $result;
        }

        $nodeIDs = array();
        foreach( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $nodeIDs[] = $node->attribute( 'node_id' );
        }
        // @todo handle Content object without nodes ?
        eZContentObjectTreeNode::removeSubtrees( $nodeIDs, $moveToTrash );

        $result->status = new ezpRestHttpResponse( 204, '' );
        return $result;
    }

    /**
     * Handle hide or unhide request for a location from its remote id
     *
     * Request:
     * - POST /content/locations?remoteId=<remoteId>&hide=<status>
     *
     * @return ezpRestMvcResult
     */
    public function doHideUnhide()
    {
        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['remoteId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "remoteId" parameter is missing'
            );
            return $result;
        }
        if ( !isset( $this->request->get['hide'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "hide" parameter is missing'
            );
            return $result;
        }
        $remoteId = $this->request->get['remoteId'];
        $hide = (bool) $this->request->get['hide'];
        $node = eZContentObjectTreeNode::fetchByRemoteID( $remoteId);
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                "Cannot find the node with the remote id {$remoteId}"
            );
            return $result;
        }
        if ( $hide )
        {
            eZContentObjectTreeNode::hideSubTree( $node );
        }
        else
        {
            eZContentObjectTreeNode::unhideSubTree( $node );
        }
        $result->status = new ezpRestHttpResponse( 204, '' );
        return $result;
    }

    /**
     * Handle move operation of a location from its remote id to another
     * location
     *
     * Request:
     * - PUT /content/locations/remote/<remoteId>/parent?destParentRemoteId=<dest>
     *
     * @return ezpRestMvcResult
     */
    public function doMove()
    {
        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['destParentRemoteId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "destParentRemoteId" parameter is missing'
            );
            return $result;
        }
        $destParentRemoteId = $this->request->get['destParentRemoteId'];
        $node = eZContentObjectTreeNode::fetchByRemoteID( $this->remoteId );
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Cannot find the node with the remote id '{$this->remoteId}'"
            );
            return $result;
        }
        $dest = eZContentObjectTreeNode::fetchByRemoteID( $destParentRemoteId );
        if ( !$dest instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Cannot find the node with the remote id '{$destParentRemoteId}'"
            );
            return $result;
        }
        eZContentOperationCollection::moveNode(
            $node->attribute( 'node_id' ),
            $node->attribute( 'contentobject_id' ),
            $dest->attribute( 'node_id' )
        );

        $newNode = eZContentObjectTreeNode::fetch( $node->attribute( 'node_id' ) );
        $result->variables['Location'] = new contentStagingLocation( $newNode );
        return $result;

    }


    /**
     * Handle DELETE request for a location from its remote id
     *
     * Request:
     * - DELETE /content/locations?remoteId=<remoteId>&trash=0|1
     *
     * @return ezpRestMvcResult
     */
    public function doRemoveLocation()
    {
        $moveToTrash = true;
        if ( isset( $this->request->get['trash'] ) )
        {
            $moveToTrash = (bool)$this->request->get['trash'];
        }

        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['remoteId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "remoteId" parameter is missing'
            );
            return $result;
        }
        $remoteId = $this->request->get['remoteId'];
        $node = eZContentObjectTreeNode::fetchByRemoteID( $remoteId );
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Node with remote id '{$remoteId}' not found"
            );
            return $result;
        }

        eZContentObjectTreeNode::removeSubtrees(
            array( $node->attribute( 'node_id' ) ),
            $moveToTrash
        );

        $result->status = new ezpRestHttpResponse( 204, '' );
        return $result;
    }

    /**
     * Handle the PUT request for a content object from its remote id to add a
     * location to it
     *
     * Request:
     * - PUT /content/objects/remote/:remoteId/locations?parentRemoteId=<parentNodeRemoteID>
     *
     * @return ezpRestMvcResult
     */
    public function doAddLocation()
    {
        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['parentRemoteId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST, 'The "parentRemoteId" parameter is missing'
            );
            return $result;
        }
        $parentRemoteId = $this->request->get['parentRemoteId'];

        $parentNode = eZContentObjectTreeNode::fetchByRemoteID( $parentRemoteId );
        if ( !$parentNode instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                "Cannot find the node with the remote id {$parentRemoteId}"
            );
            return $result;
        }
        $object  = eZContentObject::fetchByRemoteID( $this->remoteId );
        if ( !$object instanceof eZContentObject )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                'Cannot find the content object with the remote id ' . $this->remoteId
            );
            return $result;
        }

        $nodes = $object->attribute( 'assigned_nodes' );
        foreach( $nodes as $node )
        {
            if ( $node->attribute( 'parent_node_id' ) == $parentNode->attribute( 'node_id' ) )
            {
                $result->status = new ezpRestHttpResponse(
                    ezpHttpResponseCodes::FORBIDDEN,
                    "The object '{$this->remoteId}' already has a location under node '{$parentRemoteId}'"
                );
                return $result;
            }
        }

        $newNode = $this->addAssignment(
            $object, $parentNode,
            $this->request->inputVariables['remoteId'],
            (int)$this->request->inputVariables['priority'],
            $this->getSortField( $this->request->inputVariables['sortField'] ),
            $this->getSortOrder( $this->request->inputVariables['sortOrder'] )
        );

        $result->variables['Location'] = new contentStagingLocation( $newNode );
        return $result;
    }


    /**
     * Returns the eZContentObjectTreeNode::SORT_ORDER_* constant corresponding
     * to the $stringSortOrder
     *
     * @param string $stringSortOrder
     * @return int
     */
    protected function getSortOrder( $stringSortOrder )
    {
        // @todo throw an exception if $stringSortOrder is not ASC or DESC ?
        $sortOrder = eZContentObjectTreeNode::SORT_ORDER_ASC;
        if ( $stringSortOrder != 'ASC' )
        {
            $sortOrder = eZContentObjectTreeNode::SORT_ORDER_DESC;
        }
        return $sortOrder;
    }

    /**
     * Returns the eZContentObjectTreeNode::SORT_FIELD_* constant corresponding
     * to the $stringSortField
     *
     * @param string $stringSortField
     * @return int
     */
    protected function getSortField( $stringSortField )
    {
        $field = eZContentObjectTreeNode::sortFieldID( strtolower( $stringSortField ) );
        if ( $field === null )
        {
            // field might be null if sortFieldID does not recognize its
            // parameter
            // @todo throw an exception instead ?
            $field = eZContentObjectTreeNode::SORT_FIELD_PATH;
        }
        return $field;
    }


    /**
     * Create a new location for the $object under the $parent node.
     *
     * @param eZContentObject $object
     * @param eZContentObjectTreeNode $parent
     * @param string $newNodeRemoteId
     * @param int $priority
     * @param int $sortField
     * @param int $sortOrder
     * @return eZContentObjectTreeNode
     */
    protected function addAssignment( eZContentObject $object, eZContentObjectTreeNode $parent, $newNodeRemoteId, $priority, $sortField, $sortOrder )
    {
        $db = eZDB::instance();
        $db->begin();
        $newNode = $object->addLocation( $parent->attribute( 'node_id' ), true );
        $newNode->setAttribute( 'contentobject_is_published', 1 );
        $newNode->setAttribute( 'main_node_id', $object->attribute( 'main_node_id' ) );
        $newNode->setAttribute( 'remote_id', $newNodeRemoteId );
        $newNode->setAttribute( 'priority', $priority );
        $newNode->setAttribute( 'sort_field', $sortField );
        $newNode->setAttribute( 'sort_order', $sortOrder );
        // Make sure the url alias is set updated.
        $newNode->updateSubTreePath();
        $newNode->sync();
        $db->commit();
        return $newNode;
    }

}

