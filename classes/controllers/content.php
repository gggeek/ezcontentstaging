<?php
/**
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2013 eZ Systems AS. All rights reserved.
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
     * @return ezpRestMvcResult
     */
    public function doLoad()
    {

        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !$object->attribute( 'can_read' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingContent( $object );
        return $result;

    }

    /**
     * Handle DELETE request for a content object from its [remote] id
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

        if ( !$object->attribute( 'can_remove' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $moveToTrash = true;
        if ( isset( $this->request->get['trash'] ) )
        {
            $moveToTrash = ( $this->request->get['trash'] !== 'false' );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingContent::remove( $object, $moveToTrash );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle DELETE request for a content object from its [remote] id
     *
     * Request:
     * - DELETE /api/contentstaging/content/objects/remote/<remoteId>/languages/<language>
     * - DELETE /api/contentstaging/content/objects/<Id>/languages/<language>
     *
     * @return ezpRestMvcResult
     */
    public function doRemoveLanguage()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        /// @todo add perms checking

        $objectId = $object->attribute( 'id' );
        $languages = $object->allLanguages();
        if ( !isset( $languages[$this->language] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Translation in '{$this->language}' not found in the content '$objectId'" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $lang = $languages[$this->language];
        if ( !eZContentStagingContent::removeTranslations( $object, array( $lang->attribute( 'id' ) ) ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle POST request to create a content object
     *
     * Request:
     * - POST /content/objects?(parentRemoteId=<XX>|parentId=<YY>)[sectionId=<SectId>]
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
            /// @todo validate that section exists!
            $sectionId = (int) $this->request->get['sectionId'];
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        if (!$node->canCreate() )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $object = eZContentStagingContent::createContent( $node, $this->getRequestVariables(), $sectionId );
        if ( !$object instanceof eZContentObject )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $object );
        }

        // generate a 201 response
        $result = new ezpRestMvcResult();
        $result->status = new eZContentStagingCreatedHttpResponse(
            '/content/objects/' . $object->attribute( 'id' ) . '/versions/1'
        );
        return $result;
    }

    /**
     * Handle update of the always available flag or the initial language id
     *
     * Request:
     * - PUT /content/objects/remote/<remoteId>
     * - PUT /content/objects/<Id>
     *
     * @return ezpRestMvcResult
     */
    public function doUpdate()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !$object->attribute( 'can_edit' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $inputVariables = $this->getRequestVariables();
        if ( isset( $inputVariables['alwaysAvailable'] ) )
        {
            eZContentStagingContent::updateAlwaysAvailable(
                $object,
                (bool)$inputVariables['alwaysAvailable']
            );
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        if ( isset( $inputVariables['initialLanguage'] ) )
        {
            $lang = $inputVariables['initialLanguage'];
            $languages = $object->allLanguages();
            if ( !isset( $languages[$lang] ) )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Translation in '$lang' not found in the content '{$object->attribute( 'id' )}'" );
            }
            eZContentStagingContent::updateInitialLanguage(
                $object,
                $languages[$lang]->attribute( 'id' )
            );
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        if ( isset( $inputVariables['remoteId'] ) )
        {
            if ( ( $result = eZContentStagingContent::updateRemoteId(
                       $object,
                       $inputVariables['remoteId'] )
                 ) !== 0 )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $result );
            }
            //$result->status = new ezpRestHttpResponse( 204 );
        }

        /*if ( isset( $inputVariables['fields'] )
                && count( $inputVariables['fields'] ) > 0 )
        {
            // whole update

            // workaround to be able to publish (bug #018337)
            $moduleRepositories = eZModule::activeModuleRepositories();
            eZModule::setGlobalPathList( $moduleRepositories );

            $object = eZContentStagingContent::updateContent( $object, $inputVariables );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $object );
            }
        }*/

        $result = new ezpRestMvcResult();
        $result->variables = (array) new eZContentStagingContent( $object );
        return $result;
    }

    /**
     * Handle creation of a new version for an existing object
     *
     * Request:
     * - PUT /content/objects/remote/<remoteId>/versions
     * - PUT /content/objects/<Id>/versions
     *
     * @return ezpRestMvcResult
     */
    public function doAddVersion()
    {
        $inputVariables = $this->getRequestVariables();
        if ( !isset(  $inputVariables['fields'] ) || !is_array( $inputVariables['fields'] ) )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, 'The "fields" parameters is missing or not an array' );
        }

        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !$object->attribute( 'can_edit' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        // workaround to be able to publish (bug #018337)
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $version = eZContentStagingContent::updateContent( $object, $inputVariables );
        if ( !$version instanceof eZContentObjectVersion )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, $version );
        }

        $result = new ezpRestMvcResult();
        $result->status = new eZContentStagingCreatedHttpResponse(
            '/content/objects/' . $object->attribute( 'id' ) . '/versions/' . $version->attribute( 'version' )
        );
        return $result;
    }

    /**
     * @todo move logic to model
     */
    public function doPublishVersion()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        if ( !$object->attribute( 'can_edit' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        $version = $object->version( $this->versionNr );
        if ( !$version instanceof eZContentObjectVersion )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, "Version {$this->versionNr} not found" );
        }

        $status = $version->attribute( 'status' );
        if ( $status != eZContentObjectVersion::STATUS_DRAFT )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, "Version {$this->versionNr} not in DRAFT status" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        if ( eZContentStagingContent::publishVersion( $object, $version ) != 0 )
        {
            return self::errorResult( ezpHttpResponseCodes::BAD_REQUEST, "Error while publishing version" );
        }

        // in case version created is the 1st, return location address
        $result = new ezpRestMvcResult();
        if ( $version->attribute( 'version' ) == 1 )
        {
            $refresh = eZContentObject::fetch( $object->attribute( 'id' ) );
            // nb: if there is a workflow, object might be pending and have no node
            $node = $refresh->attribute( 'main_node' );
            if ( $node != null )
            {
                $result->status = new eZContentStagingCreatedHttpResponse(
                    '/content/locations/' . $node->attribute( 'node_id' )
                    );
            }
            else
            {
                // try to use obj.version.temp_main_node instead ?
                //$version = $refresh->version( 1 );
                //$node = $version->attribute( 'temp_main_node' ) );
                $result->status = new ezpRestHttpResponse( 204 );
            }

        }
        else
        {
            $result->status = new ezpRestHttpResponse( 204 );
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
    /*public function doRemoveTranslation()
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

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingContent::removeTranslations(
            $object,
            array( $languages[$this->localeCode]->attribute( 'id' ) )
        );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }*/

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

        if ( eZUser::currentUser()->canAssignSectionToObject( $sectionId, $object ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied" );
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        eZContentStagingContent::updateSection( $object, $sectionId );

        $result = new ezpRestMvcResult();
        $result->status = new ezpRestHttpResponse( 204 );
        return $result;
    }

    /**
     * Handle change section for a content object from its remote id
     *
     * Request:
     * - PUT /content/objects/remote/<remoteId>/states
     * - PUT /content/objects/<Id>/states
     *
     * @return ezpRestMvcResult
     *
     * @ todo...
     */
    public function doUpdateStates()
    {
        $object = $this->object();
        if ( !$object instanceof eZContentObject )
        {
            return $object;
        }

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $states = array();
        foreach( $this->getRequestVariables() as $stateGroup => $state )
        {
            $groupObj = eZContentObjectStateGroup::fetchByIdentifier( $stateGroup );
            if ( $groupObj )
            {
                $stateObj = $groupObj->stateByIdentifier( $state );
                if ( $stateObj )
                {
                    $states[$groupObj->attribute( 'id' )] = $stateObj->attribute( 'id' );
                }
                else
                {
                    return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "State '$state' not found in group '$stateGroup'" );
                }
            }
            else
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "State group '$stateGroup' not found" );
            }
        }

        //@todo warn not allowed states
        eZContentStagingContent::updateStates( $object, $states );

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
     * NB: checks user permissions
     *
     * @return ezpRestMvcResult
     *
     * @todo add support for parentId besides parentRemoteId
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

        $nodes = $object->attribute( 'assigned_nodes' );
        $inputVariables = $this->getRequestVariables();
        foreach ( $nodes as $node )
        {
            if ( $node->attribute( 'parent_node_id' ) == $parentNode->attribute( 'node_id' ) )
            {
                return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "The object '{$inputVariables['remoteId']}' already has a location under of location '{$parentRemoteId}'" );
                /*$result = new ezpRestMvcResult();
                $result->status = new ezpRestHttpResponse( 403 );
                return $result;*/
            }
            elseif( !$node->attribute( 'can_add_location' ) )
            {
                return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Authorization required" );
            }
        }

        /// @todo validate location input received: are priority, sortField, sortOrder mandatory?

        // workaround bug #0xxx to be able to publish
        $moduleRepositories = eZModule::activeModuleRepositories();
        eZModule::setGlobalPathList( $moduleRepositories );

        $newNode = eZContentStagingContent::addLocation(
            $object, $parentNode,
            $inputVariables['remoteId'],
            isset( $inputVariables['priority'] ) ? $inputVariables['priority'] : null,
            isset( $inputVariables['sortField'] ) ? $inputVariables['sortField'] : null,
            isset( $inputVariables['sortOrder'] ) ? $inputVariables['sortOrder'] : null
        );
        /// @todo return a 401 in case of permission problems!
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
        $object = null;
        if ( isset( $this->remoteId ) )
        {
            $object = eZContentObject::fetchByRemoteID( $this->remoteId );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Content with remote id '{$this->remoteId}' not found" );
            }
            return $object;
        }
        if ( isset( $this->Id ) )
        {
            $object = eZContentObject::fetch( $this->Id );
            if ( !$object instanceof eZContentObject )
            {
                return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Content with id '{$this->Id}' not found" );
            }
        }

        if ( !$object instanceof eZContentObject )
        {
            return self::errorResult( ezpHttpResponseCodes::NOT_FOUND, "Content not found" );
        }

        if ( !$object->attribute( 'can_read' ) )
        {
            return self::errorResult( ezpHttpResponseCodes::FORBIDDEN, "Access denied for content with id '{$this->Id}' for user " . eZUser::currentUser()->attribute( 'login' ) );
        }

        return $object;
    }

}
