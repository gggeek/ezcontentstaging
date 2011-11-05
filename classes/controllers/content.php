<?php
/**
 * @package ezcontentstaging
 *
 * @version $Id$;
 *
 * @author
 * @copyright
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class contentStagingRestContentController extends contentStagingRestBaseController
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
        $result->variables['Content'] = new contentStagingContent( $object );
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
    public function doCreateContent()
    {
        /// @todo add support for parent Id besides parent remote Id

        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['parentRemoteId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "parentRemoteId" parameter is missing'
            );
            return $result;
        }

        $remoteId = $this->request->get['parentRemoteId'];
        $node = eZContentObjectTreeNode::fetchByRemoteID( $remoteId );
        if ( !$node instanceof eZContentObjectTreeNode )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Cannot find the location with remote id '{$remoteId}'"
            );
            return $result;
        }

        // workaround to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        /// @todo we should support creation failure here
        $content = $this->createContent( $node );

        // generate a 201 response
        $result->status = new contentStagingCreatedHttpResponse(
            array(
                'Content' => '/content/objects/' . $content->attribute( 'id' )
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
     * @return ezpRestMvcResult
     */
    public function doUpdateContent()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $result = new ezpRestMvcResult();
        if ( isset( $this->request->inputVariables['alwaysAvailable'] )
                && count( $this->request->inputVariables ) === 1 )
        {
            eZContentOperationCollection::updateAlwaysAvailable(
                $object->attribute( 'id' ),
                (bool)$this->request->inputVariables['alwaysAvailable']
            );
            $result->status = new ezpRestHttpResponse( 204 );
        }
        elseif ( isset( $this->request->inputVariables['initialLanguage'] )
                && count( $this->request->inputVariables ) === 1 )
        {
            eZContentOperationCollection::updateInitialLanguage(
                $object->attribute( 'id' ),
                (int)$this->request->inputVariables['initialLanguage']
            );
            $result->status = new ezpRestHttpResponse( 204 );
        }
        elseif ( isset( $this->request->inputVariables['fields'] )
                && count( $this->request->inputVariables['fields'] ) > 0 )
        {
            // whole update
            // workaround to be able to publish
            $moduleRepositories = eZModule::activeModuleRepositories();
            eZModule::setGlobalPathList( $moduleRepositories );
            $object =$this->updateContent( $object );
            $result->variables['content'] = new contentStagingContent( $object );
        }

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
     */
    public function doRemoveTranslation()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $result = new ezpRestMvcResult();
        $objectId = $object->attribute( 'id' );
        $languages = $object->allLanguages();
        if ( !isset( $languages[$this->localeCode] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Translation in '{$this->localeCode}' not found in the content '$objectId'"
            );
            return $result;
        }

        eZContentOperationCollection::removeTranslation(
            $objectId,
            array( $languages[$this->localeCode]->attribute( 'id' ) )
        );

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
     */
    public function doUpdateSection()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        $result = new ezpRestMvcResult();
        if ( !isset( $this->request->get['sectionId'] ) )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::BAD_REQUEST,
                'The "sectionId" parameter is missing'
            );
            return $result;
        }
        $sectionId = $this->request->get['sectionId'];
        $section = eZSection::fetch( $sectionId );
        if ( !$section instanceof eZSection )
        {
            $result->status = new ezpRestHttpResponse(
                ezpHttpResponseCodes::NOT_FOUND,
                "Section with Id '$sectionId' not found"
            );
            return $result;
        }

        eZContentObjectTreeNode::assignSectionToSubTree(
            $object->attribute( 'main_node_id' ),
            $sectionId
        );

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
                ezpHttpResponseCodes::NOT_FOUND,
                "Cannot find the location with remote id '{$parentRemoteId}'"
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
                    "The object '{$this->remoteId}' already has a location under of location '{$parentRemoteId}'"
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


    // *** helper methods ***

    /// @todo assert error if neither Id nor remoteId are present
    protected function object()
    {
        if ( isset( $this->remoteId ) )
        {
            $object = eZContentObject::fetchByRemoteID( $this->remoteId );
            if ( !$object instanceof eZContentObject )
            {
                $result->status = new ezpRestHttpResponse(
                    ezpHttpResponseCodes::NOT_FOUND,
                    "Content with remote id '{$this->remoteId}' not found"
                );
                return $result;
            }
            return $node;
        }
        if ( isset( $this->Id ) )
        {
            $object = eZContentObject::fetch( $this->Id );
            if ( !$object instanceof eZContentObject )
            {
                $result->status = new ezpRestHttpResponse(
                    ezpHttpResponseCodes::NOT_FOUND,
                    "Content with id '{$this->Id}' not found"
                );
                return $result;
            }
        }
        return $object;
    }

    /**
     * Updates the $object with the provided fields in the request
     *
     * @param eZContentObject $object
     * @return eZContentObject
     */
    protected function updateContent( eZContentObject $object )
    {
        $input = $this->request->inputVariables;
        $db = eZDB::instance();
        $db->begin();
        $version = $object->createNewVersionIn( $input['localeCode'] );
        $version->setAttribute( 'modified', time() );
        $version->store();

        $this->updateAttributesList(
            $version->attribute( 'contentobject_attributes' ),
            $input['fields']
        );
        $db->commit();

        $operationResult = eZOperationHandler::execute(
            'content', 'publish',
            array(
                'object_id' => $object->attribute( 'id' ),
                'version' => $version->attribute( 'version' )
             )
        );
        return eZContentObject::fetch( $object->attribute( 'id' ) );
    }

    /**
     * Updates the eZContentObjectAttribute in $attributes with the values
     * provided in $fields
     *
     * @param array $attributes array of eZContentObjectAttribute to update
     * @param array $fields
     */
    protected function updateAttributesList( array $attributes, array $fields )
    {
        foreach ( $attributes as $attribute )
        {
            $identifier = $attribute->attribute( 'contentclass_attribute_identifier' );
            if ( !isset( $fields[$identifier] ) )
            {
                continue;
            }
            $field = $fields[$identifier];
            switch( $field['fieldDef'] )
            {
                case 'ezimage':
                case 'ezbinaryfile':
                case 'ezmedia':
                {
                    $tmpDir = eZINI::instance()->variable( 'FileSettings', 'TemporaryDir' ) . '/' . uniqid();
                    // todo use the origin filename when it'll be available
                    $fileName = uniqid();

                    eZFile::create( $fileName, $tmpDir, base64_decode( $field['value'] ) );
                    $field['value'] = $tmpDir . '/' . $fileName;
                }
                default:
            }
            $attribute->fromString( $field['value'] );
            $attribute->store();
            if ( isset( $tmpDir ) )
            {
                eZDir::recursiveDelete( $tmpDir, false );
                unset( $tmpDir );
            }
        }
    }

    /**
     * Create a content under $parent with the input variables
     *
     * @param eZContentObjectTreeNode $parent
     * @return eZContentObject
     */
    protected function createContent( eZContentObjectTreeNode $parent )
    {
        $input = $this->request->post; // shouldn't it be inputVariables ? but it's empty ?
        $class = eZContentClass::fetchByIdentifier( $input['contentType'] );
        if ( !$class instanceof eZContentClass )
        {
            throw new RuntimeException(
                'Unable to load the class with identifier ' . $input['contentType']
            );
        }
        $db = eZDB::instance();
        $db->begin();
        $content = $class->instantiateIn( $input['initialLanguage'] );
        $content->setAttribute( 'remote_id', $input['remoteId'] );
        $content->store();

        $nodeAssignment = eZNodeAssignment::create(
            array(
                'contentobject_id' => $content->attribute( 'id' ),
                'contentobject_version' => $content->attribute( 'current_version' ),
                'parent_node' => $parent->attribute( 'node_id' ),
                'is_main' => 1,
                'sort_field' => $class->attribute( 'sort_field' ),
                'sort_order' => $class->attribute( 'sort_order' )
            )
        );
        $nodeAssignment->store();

        $version = $content->version( 1 );
        $version->setAttribute( 'modified', time() );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $version->store();

        $attributes = $content->contentObjectAttributes( true, false, $input['initialLanguage'] );
        $this->updateAttributesList( $attributes, $input['fields'] );
        $db->commit();

        $operationResult = eZOperationHandler::execute(
            'content', 'publish',
            array(
                'object_id' => $content->attribute( 'id' ),
                'version' => 1
             )
        );
        return $content;
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

