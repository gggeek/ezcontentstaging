{**
  View use to display results of "single item" content sync
  (nb: single item might be synced to many targets in one go)

  @param array of eZContentStagingEvent $sync_events
  @param array of string sync_errors
  @param array of string sync_results
*}

Errors:
{$sync_errors|attribute(show)}

Results:
{$sync_results|attribute(show)}