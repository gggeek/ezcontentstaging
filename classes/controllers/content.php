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
                404, "Content with remote id '{$this->remoteId}' not found"
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

}

