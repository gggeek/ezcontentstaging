CREATE SEQUENCE ezcontentstaging_event_s START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1;

CREATE TABLE ezcontentstaging_event (
  id integer DEFAULT nextval('ezcontentstaging_event_s'::text) NOT NULL,
  target_id character varying(255) DEFAULT ''::character varying NOT NULL, -- target server
  object_id integer DEFAULT 0 NOT NULL, -- source object
  language_mask integer, -- non null only for publishing events
  to_sync integer DEFAULT 0 NOT NULL, -- bit field indicating what to sync
  modified integer DEFAULT 0 NOT NULL, -- creation date of this item
  data_text text,
  status integer DEFAULT 0 NOT NULL, -- 0: to sync, 1: syncing, 2: suspended,
  sync_begin_date integer
);

CREATE TABLE ezcontentstaging_event_node (
  event_id integer DEFAULT 0 NOT NULL,
  node_id integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY ezcontentstaging_event ADD CONSTRAINT ezcontentstaging_event_pkey PRIMARY KEY (id);
ALTER TABLE ONLY ezcontentstaging_event_node ADD CONSTRAINT ezcontentstaging_event_node_pkey PRIMARY KEY (event_id, node_id);

-- CREATE INDEX ezcontentstaging_event_target_object ON ezcontentstaging_event USING btree (target_id, object_id);
