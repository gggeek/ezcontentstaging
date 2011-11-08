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
 * @todo finish moving all content-modification-actions to the model
 * @todo decide how much typecast we do on parameters passed to calls of model's methods
 */

class eZContentStagingRestContentController extends eZContentStagingRestBaseController
{

    // *** rest actions ***

    /**
     * Handle GET on an oject from its [remote] id
     *
     * Requests:
     * - GET /content/objects/remote/<remoteId>
     * - GET /content/objects/<Id>
     *
     * @return void
     */
    public function doLoad()
    {

        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingContent( $object );
        return $result;

    }

    /**
     * Handle DELETE request for a content object from its remote id
     *
     * Request:
     * - DELETE /api/contentstaging/content/objects/remote/<remoteId>[?trash=true|false]
     * - DELETE /api/contentstaging/content/objects/<Id>[?trash=true|false]
     *
     * @return ezpRestMvcResult
     * @todo move logic to model
     */
    public function doRemove()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $result = new ezpRestMvcResult();
        $moveToTrash = true;
        if ( isset( $this->request->get['trash'] ) )
        {
            $moveToTrash = ( $this->request->get['trash'] === 'true' );
        }

        $nodeIDs = array();
        foreach( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $nodeIDs[] = $node->attribute( 'node_id' );
        }
        // @todo handle Content object without nodes ?
        eZContentObjectTreeNode::removeSubtrees( $nodeIDs, $moveToTrash );

        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle POST request to create a content object
     *
     * Request:
     * - POST /content/objects
     *
     * @return ezpRestMvcResult
     */
    public function doCreate()
    {
        if ( !isset( $this->request->get['parentRemoteId'] ) && !isset( $this->request->get['parentId'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "parentRemoteId"or "parentId" parameters are missing' );
        }

        if ( isset( $this->request->get['parentRemoteId'] ) )
        {
            $parentRemoteId = $this->request->get['parentRemoteId'];
            $node = eZContentObjectTreeNode::fetchByRemoteID( $parentRemoteId );
            if ( !$node instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Cannot find the location with remote id '{$parentRemoteId}'" );
            }
        }
        else
        {
            $parentId = $this->request->get['parentId'];
            $node = eZContentObjectTreeNode::fetch( $parentId );
            if ( !$node instanceof eZContentObjectTreeNode )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Cannot find the location with id '{$parentId}'" );
            }
        }

        $sectionId = null;
        if ( isset( $this->request->get['sectionId'] ) )
        {
            $sectionId = (int) $this->request->get['sectionId'];
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $object = eZContentStagingContent::createContent( $node, $this->request->inputVariables, $sectionId );
        if ( !$object instanceof eZContentObject )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $object );
        }

        // generate a 201 response
        $result = new ezpRestMvcResult();
        $result->status = new eZContentStagingCreatedHttpResponse(
            array(
                'Content' => '/content/objects/' . $object->attribute( 'id' )
            )
        );
        return $result;
    }

    /**
     * Handle update of the always available flag or the initial language id
     * or the whole content from its remote id
     *
     * Request:
     * - PUT /content/objects/remote/<remoteId>
     * - PUT /content/objects/<Id>
     *
     * @return ezpRestMvcResult
     * @todo move logic to model
     */
    public function doUpdate()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( isset( $this->request->inputVariables['alwaysAvailable'] ) )
        {
            eZContentOperationCollection::updateAlwaysAvailable(
                $object->attribute( 'id' ),
                (bool)$this->request->inputVariables['alwaysAvailable']
            );
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        if ( isset( $this->request->inputVariables['initialLanguage'] ) )
        {
            eZContentOperationCollection::updateInitialLanguage(
                $object->attribute( 'id' ),
                $this->request->inputVariables['initialLanguage']
            );
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        if ( isset( $this->request->inputVariables['remoteId'] ) )
        {
            if ( ( $result = eZContentStagingContent::updateRemoteId(
                       $object,
                       $this->request->inputVariables['remoteId'] )
                 ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        if ( isset( $this->request->inputVariables['fields'] )
                && count( $this->request->inputVariables['fields'] ) > 0 )
        {
            // whole update

            // workaround to be able to publish (bug #018337)
            $moduleRepositories = eZModule::activeModuleRepositories();
            eZModule::setGlobalPathList( $moduleRepositories );

            $object = eZContentStagingContent::updateContent( $object, $this->request->inputVariables );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $object );
            }
        }

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingContent( $object );
        return $result;
    }

    /**
     * Handle DELETE request for a translation of content object
     *
     * Request:
     * - DELETE /content/objects/remote/<remoteId>/translations/<localeCode>
     * - DELETE /content/objects/<Id>/translations/<localeCode>
     *
     * @return ezpRestMvcResult
     * @todo move logic to model
     */
    public function doRemoveTranslation()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $objectId = $object->attribute( 'id' );
        $languages = $object->allLanguages();
        if ( !isset( $languages[$this->localeCode] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Translation in '{$this->localeCode}' not found in the content '$objectId'" );
        }

        eZContentOperationCollection::removeTranslation(
            $objectId,
            array( $languages[$this->localeCode]->attribute( 'id' ) )
        );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle change section for a content object from its remote id
     *
     * Request:
     * - PUT /content/objects/remote/<remoteId>/section?sectionId=<sectionId>
     * - PUT /content/objects/<Id>/section?sectionId=<sectionId>
     *
     * @return ezpRestMvcResult
     * @todo move logic to model
     */
    public function doUpdateSection()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !isset( $this->request->get['sectionId'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "sectionId" parameter is missing' );
        }
        $sectionId = $this->request->get['sectionId'];
        $section = eZSection::fetch( $sectionId );
        if ( !$section instanceof eZSection )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Section with Id '$sectionId' not found" );
        }

        eZContentObjectTreeNode::assignSectionToSubTree(
            $object->attribute( 'main_node_id' ),
            $sectionId
        );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle the PUT request for a content object from its remote id to add a
     * location to it
     *
     * Request:
     * - PUT /content/objects/remote/:remoteId/locations?parentRemoteId=<parentNodeRemoteID>
     * - PUT /content/objects/:Id/locations?parentRemoteId=<parentNodeRemoteID>
     *
     * @return ezpRestMvcResult
     */
    public function doAddLocation()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !isset( $this->request->get['parentRemoteId'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "parentRemoteId" parameter is missing' );
        }
        $parentRemoteId = $this->request->get['parentRemoteId'];

        $parentNode = eZContentObjectTreeNode::fetchByRemoteID( $parentRemoteId );
        if ( !$parentNode instanceof eZContentObjectTreeNode )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Cannot find the location with remote id '{$parentRemoteId}'" );
        }

        /// @todo are we sure we want to do this? PUT requests are supposed to be idempotent,
        /// that means we should say OK and just not add a location again
        $nodes = $object->attribute( 'assigned_nodes' );
        foreach( $nodes as $node )
        {
            if ( $node->attribute( 'parent_node_id' ) == $parentNode->attribute( 'node_id' ) )
            {
                return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "The object '{$this->remoteId}' already has a location under of location '{$parentRemoteId}'" );
            }
        }

        $newNode = eZContentStagingContent::addAssignment(
            $object, $parentNode,
            $this->request->inputVariables['remoteId'],
            (int)$this->request->inputVariables['priority'], /// @todo why typecast only this value?
            $this->request->inputVariables['sortField'],
            $this->request->inputVariables['sortOrder']
        );
        if ( !$newNode instanceof eZContentObjectTreeNode )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $newNode );
        }

        $result = new ezpRestMvcResult();
        $result->variables['Location'] = (array) new eZContentStagingLocation( $newNode );
        return $result;
    }


    // *** helper methods ***

    /// @todo assert error if neither Id nor remoteId are present
    protected function object()
    {
        if ( isset( $this->remoteId ) )
        {
            $object = eZContentObject::fetchByRemoteID( $this->remoteId );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Content with remote id '{$this->remoteId}' not found" );
            }
            return $node;
        }
        if ( isset( $this->Id ) )
        {
            $object = eZContentObject::fetch( $this->Id );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Content with id '{$this->Id}' not found" );
            }
        }
        return $object;
    }

}

