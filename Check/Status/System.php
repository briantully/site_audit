<?php
/**
 * @file
 * Contains \SiteAudit\Check\Status\System.
 */

class SiteAuditCheckStatusSystem extends SiteAuditCheckAbstract {
  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('System Status');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Drupal\'s status report.');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultFail().
   */
  public function getResultFail() {
    return $this->getResultPass();
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultInfo().
   */
  public function getResultInfo() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultPass().
   */
  public function getResultPass() {
    $items = array();
    foreach ($this->registry['requirements'] as $requirement) {
      // Reduce verbosity.
      if (!drush_get_option('detail') && $requirement['severity'] < REQUIREMENT_WARNING) {
        continue;
      }

      // Title: severity - value
      if ($requirement['severity'] == REQUIREMENT_INFO) {
        $class = 'info';
        $severity = 'Info';
      }
      else if ($requirement['severity'] == REQUIREMENT_OK) {
        $severity = 'Ok';
        $class = 'success';
      }
      else if ($requirement['severity'] == REQUIREMENT_WARNING) {
        $severity = 'Warning';
        $class = 'warning';
      }
      else if ($requirement['severity'] == REQUIREMENT_ERROR) {
        $severity = 'Error';
        $class = 'error';
      }

      if (drush_get_option('html')) {
        $item = array(
          'title' => $requirement['title'],
          'severity' => $severity,
          'value' => isset($requirement['value']) && $requirement['value'] ? $requirement['value'] : '&nbsp;',
          'class' => $class,
        );
      }
      else {
        $item = strip_tags($requirement['title']) . ': ' . $severity;
        if (isset($requirement['value']) && $requirement['value']) {
          $item .= ' - ' . dt('@value', array(
            '@value' => strip_tags($requirement['value']),
          ));
        }
      }
      $items[] = $item;
    }
    if (drush_get_option('html')) {
      $ret_val = '<table class="table table-condensed">';
      $ret_val .= '<thead><tr><th>Title</th><th>Severity</th><th>Value</th></thead>';
      $ret_val .= '<tbody>';
      foreach ($items as $item) {
        $ret_val .= '<tr class="' . $item['class'] . '">';
        $ret_val .= '<td>' . $item['title'] . '</td>';
        $ret_val .= '<td>' . $item['severity'] . '</td>';
        $ret_val .= '<td>' . $item['value'] . '</td>';
        $ret_val .= '</tr>';
      }
      $ret_val .= '</tbody>';
      $ret_val .= '</table>';
    }
    else {
      $separator = PHP_EOL;
      if (!drush_get_option('json')) {
        $separator .= str_repeat(' ', 4);
      }
      $ret_val = implode($separator, $items);
    }
    return $ret_val;
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultWarn().
   */
  public function getResultWarn() {
    return $this->getResultPass();
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getAction().
   */
  public function getAction() {}

  /**
   * Implements \SiteAudit\Check\Abstract\calculateScore().
   */
  public function calculateScore() {
    // https://api.drupal.org/api/drupal/modules%21system%21system.admin.inc/function/system_status/7
    // Load .install files
    include_once DRUPAL_ROOT . '/includes/install.inc';
    drupal_load_updates();

    // Check run-time requirements and status information.
    $this->registry['requirements'] = module_invoke_all('requirements', 'runtime');
    usort($this->registry['requirements'], '_system_sort_requirements');

    $this->percentOverride = 0;
    $score_each = 100 / count($this->registry['requirements']);

    $worst_severity = REQUIREMENT_INFO;
    foreach ($this->registry['requirements'] as $requirement) {
      if ($requirement['severity'] > $worst_severity) {
        $worst_severity = $requirement['severity'];
      }
      if ($requirement['severity'] == REQUIREMENT_WARNING) {
        $this->percentOverride += $score_each / 2;
      }
      else if ($requirement['severity'] != REQUIREMENT_ERROR) {
        $this->percentOverride += $score_each;
      }
    }

    $this->percentOverride = round($this->percentOverride);

    if ($this->percentOverride > 80) {
      return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_PASS;
    }
    elseif ($this->percentOverride > 60) {
      return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_FAIL;
  }
}
