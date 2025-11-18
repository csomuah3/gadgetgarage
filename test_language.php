<?php
// Quick test page for language functionality
require_once __DIR__ . '/includes/language_config.php';

echo "Current Language: " . $current_language . "<br>";
echo "Available Languages: <pre>" . print_r($available_languages, true) . "</pre>";
echo "<hr>";
echo "Sample Translations:<br>";
echo "Home: " . t('home') . "<br>";
echo "Shop: " . t('shop') . "<br>";
echo "Language: " . t('language') . "<br>";
echo "Search placeholder: " . t('search_placeholder') . "<br>";

// Test language switcher
echo "<hr>";
echo "<h3>Language Switcher Test</h3>";
echo "<form method='POST' action='actions/change_language_action.php'>";
echo "<select name='language' onchange='this.form.submit()'>";
foreach ($available_languages as $lang_code => $lang_info) {
    $selected = ($current_language === $lang_code) ? 'selected' : '';
    echo "<option value='{$lang_code}' {$selected}>{$lang_info['flag']} {$lang_info['code']}</option>";
}
echo "</select>";
echo "</form>";
?>