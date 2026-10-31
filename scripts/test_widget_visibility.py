"""Exercise the real MU-plugin callbacks without evaluating stored PHP expressions."""
import json
from pathlib import Path
import subprocess
import unittest

PLUGIN = Path(__file__).resolve().parents[1] / 'ansible/roles/wordpress/files/mu-plugins/pausatf-widget-visibility.php'

BOOTSTRAP = r'''
define('ABSPATH', '/isolated-widget-test/');
$hooks = [];
$state = ['admin' => false, 'page' => 42, 'throw' => false];
function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][$tag] = [$callback, $priority, $accepted_args];
    return true;
}
function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $callback, $priority, $accepted_args);
}
function is_admin() { return $GLOBALS['state']['admin']; }
function is_page($ids) {
    if ($GLOBALS['state']['throw']) { throw new RuntimeException('Injected conditional failure'); }
    return in_array($GLOBALS['state']['page'], $ids, true);
}
require $argv[1];
$callback = $hooks['widget_display_callback'][0];
$instance = ['content' => 'Existing widget'];
$widget = new stdClass();
$results = [];
$results['nonmatching_page'] = $callback($instance, $widget, ['widget_id' => 'custom_html-61']);
$state['page'] = 70819;
$results['matching_page'] = $callback($instance, $widget, ['widget_id' => 'custom_html-61']);
$state['page'] = 42;
$results['unrelated_widget'] = $callback($instance, $widget, ['widget_id' => 'custom_html-62']);
$state['admin'] = true;
$results['admin'] = $callback($instance, $widget, ['widget_id' => 'custom_html-61']);
$state['admin'] = false;
$state['throw'] = true;
$results['conditional_failure'] = $callback($instance, $widget, ['widget_id' => 'custom_html-61']);
$results['unsafe_evaluator_exported'] = function_exists('Pausatf\\Widgets\\eval_logic');
$results['display_hook_arguments'] = $hooks['widget_display_callback'][2];
echo json_encode($results);
'''


class WidgetVisibilityTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        result = subprocess.run(['php', '-r', BOOTSTRAP, str(PLUGIN)],
                                capture_output=True, text=True, check=True)
        cls.result = json.loads(result.stdout)

    def test_nonmatching_page_and_conditional_failure_hide_the_widget(self):
        self.assertFalse(self.result['nonmatching_page'])
        self.assertFalse(self.result['conditional_failure'])

    def test_matching_page_retains_instance(self):
        self.assertEqual(self.result['matching_page'], {'content': 'Existing widget'})

    def test_admin_and_unrelated_widgets_remain_unchanged(self):
        self.assertEqual(self.result['admin'], {'content': 'Existing widget'})
        self.assertEqual(self.result['unrelated_widget'], {'content': 'Existing widget'})

    def test_stored_php_evaluator_is_no_longer_available(self):
        self.assertFalse(self.result['unsafe_evaluator_exported'])

    def test_registered_display_hook_retains_its_three_arguments(self):
        self.assertEqual(self.result['display_hook_arguments'], 3)
