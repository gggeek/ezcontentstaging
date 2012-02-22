CREATE TABLE ezcontentstaging_event (
  id INTEGER NOT NULL,
  target_id VARCHAR2(255) NOT NULL, -- target server
  object_id INTEGER NOT NULL, -- source object
  language_mask INTEGER, -- non null only for publishing events
  to_sync INTEGER NOT NULL, -- bit field indicating what to sync
  modified INTEGER NOT NULL, -- creation date of this item
  data_text CLOB,
  status INTEGER DEFAULT 0 NOT NULL, -- 0: to sync, 1: syncing, 2: suspended,
  sync_begin_date INTEGER
);

ALTER TABLE ezcontentstaging_event
    ADD PRIMARY KEY (id);

CREATE SEQUENCE s_contentstaging_event;

CREATE TRIGGER ezcontentstaging_event_id_tr
BEFORE INSERT ON ezcontentstaging_event FOR EACH ROW WHEN (new.id IS NULL)
BEGIN
    SELECT s_contentstaging_event.nextval INTO :new.id FROM dual;
END;
/

CREATE TABLE ezcontentstaging_event_node (
  event_id INTEGER NOT NULL,
  node_id INTEGER NOT NULL
);

ALTER TABLE ezcontentstaging_event_node
    ADD PRIMARY KEY (event_id, node_id);