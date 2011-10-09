CREATE TABLE ezcontentstaging_event (
  id int(11) NOT NULL auto_increment,
  target_id varchar(255) NOT NULL, -- target server
  object_id int(11) NOT NULL, -- source object
  modified int(11) NOT NULL, -- creation date of this item
  to_sync int(11) NOT NULL, -- bit field indicating what to sync
  status int(11) NOT NULL DEFAULT 0, -- 0: to sync, 1: syncing, 2: suspended,
  sync_begin_date int(11),
  data_text longtext,
  -- KEY ( target_id, object_id )
  PRIMARY KEY( id )
);

CREATE TABLE ezcontentstaging_event_node (
  event_id int(11) NOT NULL,
  node_id int(11) NOT NULL,
  PRIMARY KEY( event_id, node_id )
);
