<?php
/**
 * The contentStagingVersion class is used to provide the representation of a Content
 * (an object) used in REST api calls.
 *
 * It mainly takes care of
 * 1. exposing the needed attributes and casting each of them in the correct type
 * 2. exposing methods to manipulate content taking structured arrays as input
 *
 * @package ezcontentstaging
 *
 * @copyright Copyright (C) 2011-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

class eZContentStagingContent extends contentStagingBase
{
    public $contentType;
    public $name;
    public $id;
    public $ownerId;
    public $sectionId;
    public $state;
    public $versionNo;
    public $creatorId;
    public $created;
    public $modified;
    public $alwaysAvailable;
    public $remoteId;
    public $locationIds;
    public $initialLanguage;
    public $fields;

    public function __construct( eZContentObject $object )
    {
        $this->contentType = $object->attribute( 'class_identifier' );
        $this->name = $object->attribute( 'name' );
        $this->id = (int)$object->attribute( 'id' );
        $this->ownerId = (int)$object->attribute( 'owner_id' );
        $this->sectionId = (int)$object->attribute( 'section_id' );

        switch ( $object->attribute( 'status' ) )
        {
            case eZContentObject::STATUS_DRAFT:
                $this->state = 'DRAFT';
                break;
            case eZContentObject::STATUS_ARCHIVED:
                $this->state = 'ARCHIVED';
                break;
            default:
                $this->state = 'PUBLISHED';
        }

        $this->versionNo = (int)$object->attribute( 'current_version' );
        $this->creatorId = (int)$object->attribute( 'current' )->attribute( 'creator_id' );
        $this->created = self::encodeDateTime( $object->attribute( 'published' ) );
        $this->modified = self::encodeDateTime( $object->attribute( 'modified' ) );
        $this->alwaysAvailable = (bool)$object->attribute( 'always_available' );
        $this->remoteId = $object->attribute( 'remote_id' );
        $this->initialLanguage = $object->attribute( 'initial_language_code' );

        $this->locationIds = array();
        /// @todo this is bad for performances, we should not fetch full nodes
        foreach ( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $this->locationIds[] = (int)$node->attribute( 'node_id' );
        }

        $this->fields = array();
        foreach ( $object->attribute( 'data_map' ) as $identifier => $attr )
        {
            /// @todo move list of datatypes that have broken implementations (for us) of has_content to a separate function
            $type = $attr->attribute( 'data_type_string' );
            if ( $attr->attribute( 'has_content' ) || $type == 'ezsrrating' || $type == 'ezuser' )
            {
                $this->fields[$identifier] = (array) new eZContentStagingField( $attr, $attr->attribute( 'language_code' ), null );
            }
        }
    }

    /**
     * Updates the $object with the provided fields in the request - ie. it creates a new version
     *
     * @param eZContentObject $object
     * @return eZContentObject|string
     */
    static public function updateContent( eZContentObject $object, $input )
    {
        $db = eZDB::instance();
        // within transactions, db errors generate a fatal halt, unless we tell
        // the db to use exceptions
        $dbhandling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $db->begin();

            /// @todo test if initialLanguage is set
            $version = $object->createNewVersionIn( $input['initialLanguage'] );

            /// @todo log an error and maybe abort instead of continuing if bad date format?
            if ( isset( $input['created'] ) && ( $time = self::decodeDatetIme( $input['created'] ) ) != 0 )
            {
                $version->setAttribute( 'created', $time );
            }
            // The modified date for now we do not allow to be synced
            $version->store();

            self::updateAttributesList(
                $version->attribute( 'contentobject_attributes' ),
                $input['fields']
            );

            $db->commit();

            /*$operationResult = eZOperationHandler::execute(
                'content', 'publish',
                array(
                    'object_id' => $object->attribute( 'id' ),
                    'version' => $version->attribute( 'version' )
                )
            );*/

            return $version; //eZContentObject::fetch( $object->attribute( 'id' ) );
        }
        catch ( exception $e )
        {
            if ( $db->transactionCounter() )
            {
                $db->rollback();
            }
            return $e->getMessage();
        }
    }

    /**
     * Updates the eZContentObjectAttribute in $attributes with the values
     * provided in $fields
     *
     * @param array $attributes array of eZContentObjectAttribute to update
     * @param array $fields
     */
    static protected function updateAttributesList( array $attributes, array $fields )
    {
        foreach ( $attributes as $attribute )
        {
            $identifier = $attribute->attribute( 'contentclass_attribute_identifier' );
            $required = $attribute->attribute( 'is_required' );
            $type = $attribute->attribute( 'data_type_string' );
            if ( !isset( $fields[$identifier] ) )
            {
                // This check is just preliminary, eg. what if we get
                // in json an empty string for a string field, or no blocks
                // for an ezpage attribute? we check it later on
                if ( $required && !$attribute->attribute( 'has_content' ) )
                {
                    throw new Exception( "Missing required attribute '$identifier'" );
                }
                continue;
            }
            if ( !isset( $fields[$identifier]['fieldDef'] ) )
            {
                throw new Exception( "Unknown type for field '$identifier'" );
            }
            if ( $type != $fields[$identifier]['fieldDef'] )
            {
                 throw new Exception( "Attribute '$identifier' should be of type $type, not '{$fields[$identifier]['fieldDef']}'" );
            }
            // nb: can not use isset here
            if ( !array_key_exists( 'value', $fields[$identifier] ) )
            {
                throw new Exception( "Missing value for field '$identifier'" );
            }
            eZContentStagingField::decodeValue( $attribute, $fields[$identifier]['value'] );
            if ( $required && !$attribute->attribute( 'has_content' ) )
            {
                throw new Exception( "Required attribute '$identifier' does not have a value" );
            }
        }
    }

    /**
     * Create a content under $parent with the input variables
     *
     * @param eZContentObjectTreeNode $parent
     * @return eZContentObject|string
     *
     * @todo fix object publication date if the parameter is received
     * @todo change user id if the parameter is received
     */
    static public function createContent( eZContentObjectTreeNode $parent, $input, $sectionId = null )
    {
        $class = eZContentClass::fetchByIdentifier( $input['contentType'] );
        if ( !$class instanceof eZContentClass )
        {
           return 'Unable to load the class with identifier ' . $input['contentType'];
        }

        $db = eZDB::instance();
        // within transactions, db errors generate a fatal halt, unless we tell
        // the db to use exceptions
        $dbhandling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            /// @todo maybe abort instead of continuing if bad date format?
            /// @todo add a check that date is not in the future?
            $creationDate = null;
            if ( isset( $input['created'] ) )
            {
                $creationDate = self::decodeDatetIme( $input['created'] );
                if ( !$creationDate )
                {
                    eZDebug::writeWarning( 'Object creation date not valid: ' . $input['created'], __METHOD__ );
                }
            }

            $db->begin();

            if ( isset( $input['initialLanguage'] ) )
            {
                /// @todo what if initial language does not exist?
                $content = $class->instantiateIn( $input['initialLanguage'] );
            }
            else
            {
                 $content = $class->instantiate();
            }

            // if remote_id is not set, just leave it as it is (will be filled by random value?)
            if ( isset( $input['remoteId'] ) )
            {
                $content->setAttribute( 'remote_id', $input['remoteId'] );
            }

            // the date set here is normally not reset during the publication process
            if ( $creationDate )
            {
                $content->setAttribute( 'published', $creationDate );
            }
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
            // The date of version creation we set to object publication date,
            // in order not to make it appear as if it was created after object publication
            if ( $creationDate )
            {
                $version->setAttribute( 'created', $creationDate );
            }
            // Version modification time is taken as current time at version creation.
            /*else
            {
                $version->setAttribute( 'modified', time() );
            }*/
            $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
            $version->store();

            $attributes = $content->contentObjectAttributes( true, false, $input['initialLanguage'] );
            self::updateAttributesList( $attributes, $input['fields'] );

            $db->commit();

            /*$operationResult = eZOperationHandler::execute(
                'content', 'publish',
                array(
                    'object_id' => $content->attribute( 'id' ),
                    'version' => 1
                )
            );*/

            return $content;
        }
        catch ( exception $e )
        {
            if ( $db->transactionCounter() )
            {
                $db->rollback();
            }
            return $e->getMessage();
        }
    }

    /**
     * @return int 0 on sucess
     * @todo use exceptions / return more meaningful errors
     */
    static public function publishVersion( eZContentObject $object, eZContentObjectVersion $version )
    {
        // we assume that this operation always exists ;-)
        $operationResult = eZOperationHandler::execute(
            'content',
            'publish',
            array(
                'object_id' => $object->attribute( 'id' ),
                'version' => $version->attribute( 'version' )
            )
        );
        // hand-tested: 1- when publication goes ok, that's what we get
        /// @todo test: is it always 1 or is it the version nr?
        //              3- when operation is suspsended to cronjob
        if ( !is_array( $operationResult ) || ( @$operationResult['status'] != 1 && @$operationResult['status'] != 3 ) )
        {
            return -1;
        }
        return 0;
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
     * @return eZContentObjectTreeNode|string
     *
     * @todo return a better ok/ko value
     * @todo make more params optional: $newNodeRemoteId
     * @todo perms checking: Is it doen always by called code?
     */
    static public function addLocation( eZContentObject $object, eZContentObjectTreeNode $parent, $newNodeRemoteId, $priority = null, $sortField = null, $sortOrder = null )
    {
        if ( $priority !== null )
        {
            $priority = (int) $priority;
        }
        if ( $sortField !== null )
        {
            $sortField = self::decodeSortField( $sortField );
        }
        if ( $sortOrder !== null )
        {
            $sortOrder = self::decodeSortOrder( $sortOrder);
        }
        $selectedNodeIDArray = array( $parent->attribute( 'node_id' ) );

        $db = eZDB::instance();
        $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $newNode = false;

            $ini = eZINI::instance( 'contentstagingtarget.ini' );
            $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
            if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_addlocation' ) )
            {
                /// @todo what if this triggers a reported action (eg. approval?)
                $operationResult = eZOperationHandler::execute(
                    'content',
                    'addlocation',
                    array(
                        'node_id' => $object->attribute( 'main_node_id' ),
                        'object_id' => $object->attribute( 'id' ),
                        'select_node_id_array' => $selectedNodeIDArray ),
                    null,
                    true );
            }
            else
            {
                $operationResult = eZContentOperationCollection::addAssignment( $object->attribute( 'main_node_id' ), $object->attribute( 'id' ), $selectedNodeIDArray );

                /* manual creation
                /// @todo do we need a transaction here?
                $db = eZDB::instance();
                $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
                try
                {
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
                    /// @bug we should be able to reset db error handler to its previous state, but there is no way to do so in the api...
                }
                catch ( exception $e )
                {
                    $db->rollback();
                    return $e->getMessage();
                }*/
            }
            /// @see eZModuleoperationInfo::execute
            // note: eZContentOperationCollection::addAssignment always returns array ( 'status' => true ).
            /// @todo Open a bug...
            if ( $operationResult == null || $operationResult['status'] != true )
            {
                throw new exception( 'New node has not been created. Possible workflow problem' );
            }

            // fetch new node by its parent and object id
            $conds = array(
                'contentobject_id' => $object->attribute( 'id' ),
                'parent_node_id' => $parent->attribute( 'node_id' ) );
            $newNode = eZPersistentObject::fetchObject(
                eZContentObjectTreeNode::definition(),
                null,
                $conds );
            if ( !$newNode )
            {
                throw new exception( 'New node has not been created. Possible permissions problem ' . var_export( $newNode, true ) );
            }

            $newNode->setAttribute( 'remote_id', $newNodeRemoteId );
            if ( $priority !== null )
            {
                $newNode->setAttribute( 'priority', $priority );
            }
            if ( $sortField !== null )
            {
                $newNode->setAttribute( 'sort_field', $sortField );
            }
            if ( $sortOrder !== null )
            {
                $newNode->setAttribute( 'sort_order', $sortOrder );
            }
            $newNode->store();
            $db->commit();
            /// @todo is it necessary to clear caches again?
            return $newNode;
        }
        catch ( exception $e )
        {
            if ( $db->transactionCounter() )
            {
                $db->rollback();
            }
            return $e->getMessage();
        }
    }

    /**
     * @todo return an ok/ko value
     * @todo add a try/catch block for transactions?
     * @todo perms checking
     */
    static public function updateSection( eZContentObject $object, $sectionId )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_updatesection' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'updatesection',
                    array(
                        'node_id' => $object->attribute( 'main_node_id' ),
                       'selected_section_id' => $sectionId ),
                null,
                true );

        }
        else
        {
            eZContentOperationCollection::updateSection( $object->attribute( 'main_node_id' ), $sectionId );
            /* manual update
            eZContentObjectTreeNode::assignSectionToSubTree(
                $object->attribute( 'main_node_id' ),
                $sectionId
            );
            */
        }
    }

    /**
     * @todo
     */
    static public function updateStates( eZContentObject $object, $states )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_updateobjectstate' ) )
        {
            $operationResult = eZOperationHandler::execute( 'content', 'updateobjectstate',
            array( 'object_id'     => $object->attribute( 'id' ),
                   'state_id_list' => $states ) );
        }
        else
        {
            eZContentOperationCollection::updateObjectState( $object->attribute( 'id' ), $states );
        }
    }

    /**
     * @param bool $moveToTrash
     *
     * @todo return an ok/ko value
     * @todo add a try/catch block for transactions?
     * @todo perms checking
     * @todo handle Content object without nodes ?
     */
    static public function remove( eZContentObject $object, $moveToTrash )
    {
        $nodeIDs = array();
        foreach ( $object->attribute( 'assigned_nodes' ) as $node )
        {
            $nodeIDs[] = $node->attribute( 'node_id' );
        }
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_delete' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'delete',
                array(
                    'node_id_list' => $nodeIDs,
                    'move_to_trash' => $moveToTrash ),
                null, true );
        }
        else
        {
            eZContentOperationCollection::deleteObject( $nodeIDs, $moveToTrash );
        }
    }

    /**
     * @todo return an ok/ko value
     * @todo add a try/catch block for transactions?
     * @todo perms checking
     */
    static public function updateInitialLanguage( eZContentObject $object, $initialLanguage )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_updateinitiallanguage' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'updateinitiallanguage',
                array(
                    'object_id' => $object->attribute( 'id' ),
                    'new_initial_language_id' => $initialLanguage,
                    // note : the $nodeID parameter is ignored here but is
                    // provided for events that need it
                    'node_id' => $object->attribute( 'main_node_id' ) ) );
        }
        else
        {
            eZContentOperationCollection::updateInitialLanguage( $object->attribute( 'id' ), $initialLanguage );
        }
    }

    /**
     * @param bool $alwaysAvailable
     * @todo return an ok/ko value
     * @todo add a try/catch block for transactions?
     * @todo perms checking
     */
    static public function updateAlwaysAvailable( eZContentObject $object, $alwaysAvailable )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_updatealwaysavailable' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'updatealwaysavailable',
                array(
                    'object_id' => $object->attribute( 'id' ),
                    'new_always_available' => $alwaysAvailable,
                    // note : the $nodeID parameter is ignored here but is
                    // provided for events that need it
                    'node_id' => $object->attribute( 'main_node_id' ) ) );
        }
        else
        {
            eZContentOperationCollection::updateAlwaysAvailable( $objectID, $alwaysAvailable );
        }
    }

    /**
     * Update the remote id of the $node
     *
     * @param eZContentObjectTreeNode $node
     * @param string $remoteId
     * @return 0|string
     */
    static public function updateRemoteId( eZContentObject $object, $remoteId )
    {
        $db = eZDB::instance();
        $handling = $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        try
        {
            $object->setAttribute( 'remote_id', $remoteId );
            $object->store();
            eZContentCacheManager::clearContentCache(
                $object->attribute( 'id' )
            );
            return 0;
            /// @bug we should be able to reset db error handler to its previous state, but there is no way to do so in the api...
        }
        catch ( exception $e )
        {
            return $e->getMessage();
        }
    }

    /**
     * @todo return an ok/ko value
     * @todo add a try/catch block for transactions?
     * @todo perms checking
     */
    static public function removeTranslations( eZContentObject $object, $translations )
    {
        $ini = eZINI::instance( 'contentstagingtarget.ini' );
        $dotriggers = ( $ini->variable( 'GeneralSettings', 'ExecuteTriggers' ) != 'disabled' );
        if ( $dotriggers && eZOperationHandler::operationIsAvailable( 'content_removetranslation' ) )
        {
            $operationResult = eZOperationHandler::execute(
                'content',
                'removetranslation',
                array(
                    'object_id' => $object->attribute( 'id' ),
                    'language_id_list' => $translations,
                    // note : the $nodeID parameter is ignored here but is
                    // provided for events that need it
                    'node_id' => $object->attribute( 'main_node_id' ) ) );
        }
        else
        {
            eZContentOperationCollection::removeTranslation( $object->attribute( 'id' ), $translations );
        }
    }
}
