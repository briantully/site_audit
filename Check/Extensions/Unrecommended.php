<?php
/**
 * @file
 * Contains \SiteAudit\Check\Extensions\Unrecommended.
 */

class SiteAuditCheckExtensionsUnrecommended extends SiteAuditCheckAbstract {
  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('Not recommended');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Check for unrecommended modules.');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultFail().
   */
  public function getResultFail() {
    $ret_val = dt('The following unrecommended modules(s) are currently enabled: @list', array(
      '@list' => implode(', ', array_keys($this->registry['extensions_unrec'])),
    ));
    if (drush_get_option('detail')) {
      if (drush_get_option('html')) {
        $ret_val = '<p>' . $ret_val . '</p>';
        $ret_val .= '<table class="table table-condensed">';
        $ret_val .= '<thead><tr><th>Name</th><th>Reason</th></thead>';
        $ret_val .= '<tbody>';
        foreach ($this->registry['extensions_unrec'] as $row) {
          $ret_val .= '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
        }
        $ret_val .= '</tbody>';
        $ret_val .= '</table>';
      }
      else {
        foreach ($this->registry['extensions_unrec'] as $row) {
          $ret_val .= PHP_EOL . '      - ' . $row[0] . ': ' . $row[1];
        }
      }
    }
    return $ret_val;
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultInfo().
   */
  public function getResultInfo() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultPass().
   */
  public function getResultPass() {
    return dt('No unrecommended extensions were detected; no action required.');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultWarn().
   */
  public function getResultWarn() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getAction().
   */
  public function getAction() {
    if ($this->getScore() != SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_PASS) {
      return dt('Disable and completely remove unrecommended modules for increased performance, stability and security in the any environment.');
    }
  }

  /**
   * Implements \SiteAudit\Check\Abstract\calculateScore().
   */
  public function calculateScore() {
    $this->registry['extensions_unrec'] = array();
    $extension_info = drush_get_extensions(FALSE);
    uasort($extension_info, '_drush_pm_sort_extensions');
    $unrecommended_extensions = $this->getExtensions();

    foreach ($extension_info as $key => $extension) {
      $row = array();

      if (!array_key_exists($extension->name, $unrecommended_extensions)) {
        unset($extension_info[$key]);
        continue;
      }

      // Name.
      $row[] = $extension->label;
      // Reason.
      $row[] = $unrecommended_extensions[$extension->name];

      $this->registry['extensions_unrec'][$extension->name] = $row;
    }

    if (!empty($this->registry['extensions_unrec'])) {
      return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_FAIL;
    }
    return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_PASS;
  }

  /**
   * Get a list of unrecommended extension names and reasons.
   * @return array
   *   Keyed by module machine name, value is explanation.
   */
  public function getExtensions() {
    $unrecommended_modules = array(
      'apc' => dt('APC is not an efficient mechanism for a Drupal caching backend and takes away space for opcode. Use redis or another caching mechanism for caching.'),
      'fast_404' => dt('Can conflict with Varnish caching; use Drupal\'s 404_fast_html instead'),
      'views_php' => dt('Unfinished and incomplete, Views PHP permits executable code to be stored in the database with no revisioning; a typo introduced in the Views UI can bring down an entire production site with no accountability. See http://api.drupal.org/api/views for details on how to implement your own custom Views functionality.'),
      'views_customfield' => dt('Views Custom Field contains the field for PHP code, which permits executable code to be stored in the database with no revisioning; a typo introduced in the Views UI can bring down an entire production site with no accountability. See http://api.drupal.org/api/views for details on how to implement your own custom Views functionality.'),
      'bad_judgement' => dt('Joke module, framework for anarchy.'),
      'misery' => dt('Joke module, degrades site performance.'),
    );
    if (drush_get_option('vendor') == 'pantheon') {
      // Unsupported or redundant.
      $pantheon_unrecommended_modules = array(
        'memcache' => dt('Pantheon does provide memcache support; redis is provided as a service to all customers; see http://helpdesk.getpantheon.com/customer/portal/articles/401317-redis'),
        'memcache_storage' => dt('Pantheon does provide memcache support; redis is provided as a service to all customers; see http://helpdesk.getpantheon.com/customer/portal/articles/401317-redis'),
        'drupal_less' => dt('Before deployment, compile and commit CSS '),
        // Backup & Migrate and related modules.
        'backup_migrate' => dt('Backup & Migrate makes your Drupal site work harder and degrades site performance; instead, use Pantheon\'s Backup through the site dashboard, which won\'t affect site performance.'),
        'backup_migrate_files' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'backup_migrate_prune' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'backup_migrate_sftp' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'backup_migrate_dropbox' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'backup_migrate_cloudfiles' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'hpcloud' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
        'nodesquirrel' => dt('Part of Backup & Migrate; use Pantheon\'s Backup instead.'),
      );
      $unrecommended_modules = array_merge($unrecommended_modules, $pantheon_unrecommended_modules);
    }
    return $unrecommended_modules;
  }
}