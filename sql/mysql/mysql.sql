CREATE TABLE ezcontentstaging_item (
  target_id varchar(255) NOT NULL, -- target server
  object_id int(11) NOT NULL, -- source object
  modified int(11) NOT NULL, -- modification date of this item
  to_sync int(11) NOT NULL, -- bitmap indicating what to sync
  status int(11) NOT NULL DEFAULT 0, -- 0: to sync, 1: syncing, 2: suspended,
  sync_begin_date int(11),
  data longtext,
  PRIMARY KEY( target_id, object_id ) );

CREATE TABLE ezcontentstaging_item_event (
  target_id varchar(255) NOT NULL, -- target server
  object_id int(11) NOT NULL, -- source object
  id int(11) NOT NULL, -- per-item seq. number
  created int(11) NOT NULL, -- modification date of this item
  type varchar(255) NOT NULL,
  data longtext,
  PRIMARY KEY( target_id, object_id, id ) );