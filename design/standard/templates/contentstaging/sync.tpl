{**
  View use to display results of "single item" content sync
  (nb: single item might be synced to many targets in one go)

  @param array of eZContentStagingItem $syncItems
  @param array of string syncErrors
  @param array of string syncResults
*}

Errors:
{$syncErrors|attribute(show)}

Results:
{$syncResults|attribute(show)}