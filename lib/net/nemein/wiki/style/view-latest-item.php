<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$history = $data['history'];
$page = $history['object'];

$version_string = "<a href=\"{$prefix}__ais/rcs/preview/{$page->guid}/{$history['revision']}\">{$history['revision']}</a>";

$url = midcom::get()->permalinks->create_permalink($page->guid);
$formatter = $data['l10n']->get_formatter();
?>
<tr>
    <td>
        <?php
        echo "<abbr class=\"dtposted\" title=\"" . gmdate('Y-m-d\TH:i:s\Z', $history['date']) . "\">" . $formatter->time($history['date']) . "</abbr>\n";
        ?>
    </td>
    <td>
        <a rel="note" class="subject url" href="&(url);">&(page.title);</a>
    </td>
    <td>
        &(version_string:h);
    </td>
    <td class="revisor">
        <?php
        if (   $history['user']
            && $user = midcom::get()->auth->get_user($history['user'])) {
            $person_label = org_openpsa_widgets_contact::get($user->guid)->show_inline();
            echo "                    {$person_label}\n";
        } elseif ($history['ip']) {
            echo "                    {$history['ip']}\n";
        }
        ?>
    </td>
    <td class="message">
        &(history['message']);
    </td>
</tr>