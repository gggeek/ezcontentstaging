CREATE TABLE ezcontentstaging_item (
  host_id varchar(255) NOT NULL,
  object_id int(11) NOT NULL,
  modified int(11) NOT NULL,
  to_sync int(11) NOT NULL,
  PRIMARY KEY( host_id, object_id ) );