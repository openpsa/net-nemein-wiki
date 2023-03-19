<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage latest handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_latest extends midcom_baseclasses_components_handler
{
    private int $_updated_pages = 0;
    private int $_max_pages = 0;
    private array $latest_pages = [];

    /**
     * List all items updated with then given timeframe
     */
    private function _seek_updated(int $from, int $to = null)
    {
        $to ??= time();

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic.component', '=', 'net.nemein.wiki');
        $qb->add_constraint('topic', 'INTREE', $this->_topic->id);
        $qb->add_constraint('metadata.revised', '<=', date('Y-m-d H:i:s', $to));
        $qb->add_constraint('metadata.revised', '>=', date('Y-m-d H:i:s', $from));
        $qb->add_order('metadata.revised', 'DESC');

        $rcs = midcom::get()->rcs;

        foreach ($qb->execute() as $page) {
            $rcs_handler = $rcs->load_backend($page);

            // Get object history
            foreach ($rcs_handler->get_history()->all() as $history) {
                if ($this->_updated_pages >= $this->_max_pages) {
                    // End here
                    return;
                }

                if (   $history['date'] < $from
                    || $history['date'] > $to) {
                    // We can ignore revisions outside the timeframe
                    continue;
                }
                $history['object'] = $page;
                $this->_add_history_entry($history);
            }
        }
    }

    private function _add_history_entry(array $entry)
    {
        $history_date = date('Y-m-d', $entry['date']);

        $this->latest_pages[$history_date] ??= [];
        $this->latest_pages[$history_date][] = $entry;
        $this->_updated_pages++;
    }

    public function _handler_latest(array &$data)
    {
        $this->_max_pages = $this->_config->get('latest_count');

        // Start by looking for items within last two weeks
        $from = strtotime('14 days ago');
        $this->_seek_updated($from);

        $i = 0;
        while (   $this->_updated_pages < $this->_max_pages
               && $i < 20) {
            // Expand seek by another two weeks
            $to = $from;
            $from = strtotime('14 days ago', $to);
            $this->_seek_updated($from, $to);
            $i++;
        }

        $data['view_title'] = sprintf($this->_l10n->get('latest updates in %s'), $this->_topic->extra);
        midcom::get()->head->set_pagetitle($data['view_title']);

        $this->add_breadcrumb('latest/', $data['view_title']);
    }

    public function _show_latest(string $handler_id, array &$data)
    {
        if (!empty($this->latest_pages)) {
            krsort($this->latest_pages);
            $dates_shown = [];
            midcom_show_style('view-latest-header');
            foreach ($this->latest_pages as $date => $versions) {
                if (!isset($dates_shown[$date])) {
                    $data['date'] = $date;
                    midcom_show_style('view-latest-date');
                    $dates_shown[$date] = true;
                }

                foreach ($versions as $history) {
                    $data['history'] = $history;
                    midcom_show_style('view-latest-item');
                }
            }

            midcom_show_style('view-latest-footer');
        }
    }
}
