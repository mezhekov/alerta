<?php
  $label = '';
  $tag = '';
  $query = '';

  foreach ($_GET as $key => $value) {
      if ($key != 'label') {
          $query_arr[] = $key . '=' . $value;
          $label_arr[] = $value;
      }
  }
  if (isset($_GET['label'])) {
      $label = $_GET['label'];
  } else {
      $label = implode(' ', $label_arr);
  }
  $query = implode('&', $query_arr);
  $tag = implode('-', $label_arr);
  $tag = preg_replace("/[^a-zA-Z-]/", "", $tag);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Alert Console - <?php echo $label; ?></title>

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
  </head>

  <body>
    <div class="container">
      <span id="heartbeat-alerts"></span>
      <div id="console-alert" class="alert alert-info initially-hidden">
        <button class="close" data-dismiss="alert" onclick="">&times;</button>
        <strong>Warning!</strong> <span id="warning-text"></span>
      </div>
      <table width="100%">
        <td>
        <table class="table table-bordered table-condensed" id="status-counts">
          <tbody>
          <tr id="Alert-status" class="status">
            <td><span class="label">OPEN</span></td>
            <td id="alert-open">0</td>
            <td><span class="label">ACK</span></td>
            <td id="alert-ack">0</td>
            <td><span class="label">CLOSED</span></td>
            <td id="alert-closed">0</td>
          </tr>
          </tbody>
        </table>
      </td><td>
        <div align="right">
        <select id="limit-select" class="btn" name="limit" onchange="updateLimit(this.value)">
          <option value="0">No limit</option>
          <option value="100">Only 100</option>
          <option value="500">Only 500</option>
          <option value="1000">Only 1000</option>
          <option value="5000">Only 5000</option>
        </select>
        <select id="from-date-select" class="btn" name="last" onchange="updateFromDate(this.value)">
          <option value="0">All alerts</option>
          <option value="120">Last 2 minutes</option>
          <option value="300">Last 5 minutes</option>
          <option value="600">Last 10 minutes</option>
          <option value="1800">Last 30 minutes</option>
          <option value="3600">Last 1 hour</option>
        </select>
        <button class="btn" id="toggle-ACK" class="toggle-ACK"><span><i class="icon-minus"></i> Hide</span><span class="initially-hidden"><i class="icon-plus"></i> Show</span> Acknowledged</button>
        <button class="btn" id="toggle-NORMAL" class="toggle-NORMAL"><span><i class="icon-minus"></i> Hide</span><span class="initially-hidden"><i class="icon-plus"></i> Show</span> Normals</button>
        <button id="refresh-all" class="console-button btn"><i class="icon-refresh"></i> Refresh Now</button>
        </div>
      </td>
      </table>

      <!-- Alert Details -->
      <div class="row show-grid">
        <div class="span12">
          <table class="table table-bordered table-condensed" id="alert-details">
            <caption class="alerts-caption">
              Production - <span id="alert-details-caption"><?php echo $label; ?></span> alert details
            </caption>
            <thead>
              <tr> <th></th><th>Severity</th><th>Status</th><th>Last Receive Time</th><th>Dupl. Count</th><th>Env.</th><th>Service</th><th>Cluster</th><th>Resource</th><th>Event</th><th>Value</th><th>Text</th></tr>
            </thead>
            <tbody id="<?php echo $tag; ?>-alerts" class="serviceAlerts">
            </tbody>
          </table>

        </div>
      </div>
      <!-- end Alert Details -->

    </div> <!-- end container -->
    <script src="js/jquery-1.7.1.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/bootstrap-tooltip.js"></script>
    <script src="js/console.js"></script>

    <script>
      $(document).ready(function() {

        heartbeatAlerts();

        var statusfilter = '<?php echo $query; ?>';

        var services = { '<?php echo $tag; ?>': statusfilter };
        loadStatus(statusfilter, true);
        loadAlerts(services, true);

        $('#refresh-all').click(function() {
          loadStatus(statusfilter, false);
          loadAlerts(services, false)
        });
      });
    </script>
    
  </body>
</html>
