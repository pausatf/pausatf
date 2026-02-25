<?php
// PA USATF member directory. Originally by Jeff Teeters, January 2007.
// Rewritten February 2026: removed redirect, replaced mysql_* with PDO,
// added input escaping, graceful handling of missing database.

error_reporting(E_ALL);
require_once('/var/www/legacy/private/db.php');

$pdo = get_pdo();

function this_script_name(): string
{
    return basename($_SERVER['SCRIPT_NAME']);
}

function club_script_name(): string
{
    return 'clubs.php';
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function get_db_param(PDO $pdo, string $param): string
{
    static $cache = [];
    if (isset($cache[$param])) {
        return $cache[$param];
    }
    $stmt = $pdo->prepare(
        "SELECT value FROM db_status WHERE table_name = 'pa_members' AND param = :param"
    );
    $stmt->execute(['param' => $param]);
    $row = $stmt->fetch();
    $value = $row ? (string) $row['value'] : '';
    $cache[$param] = $value;
    return $value;
}

function get_club_info(PDO $pdo, int $club_no): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM tblCLUBS WHERE club_no = :club_no");
    $stmt->execute(['club_no' => $club_no]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function make_lower_case_name(string $name): string
{
    // 2011-03-09: Just return as-is per Jeff Teeters.
    return $name;
}

function get_lower_case_club_name(int $club_no, string $club_name): string
{
    static $cache = [];
    if (!isset($cache[$club_no])) {
        $cache[$club_no] = make_lower_case_name($club_name);
    }
    return $cache[$club_no];
}

// --- CSS / JS helpers ---

function popup_javascript(): string
{
    return '<script type="text/javascript">
function close_window(div_id) {
    document.getElementById(div_id).style.display = "none";
}
function open_window(div_id) {
    document.getElementById(div_id).style.display = "block";
}
function toggle(div_id) {
    var el = document.getElementById(div_id);
    el.style.display = (el.style.display === "none") ? "block" : "none";
}
</script>';
}

function make_css(string $class, string $attributes): string
{
    return "<style type=\"text/css\">\n#{$class} {\n{$attributes}\n}\n</style>";
}

function make_category_css(): string
{
    $a = "position:fixed;right:100px;top:150px;"
       . "background-color:#dee2ed;padding:10px;color:black;border:8px solid #667db3;";
    return make_css("categoryDiv", $a);
}

function make_age_css(): string
{
    $a = "position:fixed;left:70px;top:150px;"
       . "background-color:#dee2ed;padding:10px;color:black;border:8px solid #667db3;";
    return make_css("ageDiv", $a);
}

function custom_css(string $id, int $x_offset = -40, int $y_offset = -65): string
{
    return '<style type="text/css">
.' . $id . ' a{position:relative;}
.' . $id . ' a span{
visibility:hidden;position:absolute;
top:' . $y_offset . 'px;left:' . $x_offset . 'px;width:140px;padding:8px;
background:#dee2ed;color:black;border:4px solid #667db3;
}
.' . $id . ' a:hover{visibility:visible}
.' . $id . ' a:hover span{visibility:visible;}
</style>';
}

function make_popup_div(string $id, string $text): string
{
    return "<div id=\"{$id}\" style=\"display:none;\">\n{$text}\n"
         . "<div style=\"text-align:center;\"><a href=\"#\" onclick=\"close_window('{$id}')\">Close</a></div>\n"
         . "</div>";
}

// --- Page layout ---

function page_header(string $title, string $sub_title = '', ?PDO $pdo = null): string
{
    $date = '';
    $time = '';
    if ($pdo !== null) {
        $last_update = get_db_param($pdo, 'last_update');
        if (strpos($last_update, ', ') !== false) {
            [$date, $time] = explode(', ', $last_update);
        }
    }
    $current_month = (int) date('n');
    $showing_renewals = $current_month >= 11;

    $h_title = h($title);
    $html = '<html><head>'
          . '<title>PA ' . $h_title . '</title>'
          . '<link href="PAstylesheetpg2.css" type="text/css" rel="stylesheet">'
          . popup_javascript() . make_category_css() . make_age_css()
          . ($showing_renewals ? custom_css('popupbox') : '')
          . '</head><body style="max-width:890px;">'
          . '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr>'
          . '<td valign="top" width="20%"><font size="2">'
          . '<a href="http://www.pausatf.org"><b>Home</b></a><br>'
          . '<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>'
          . '<td valign="top" style="text-align:center;width:60%;">'
          . '<h2>' . $h_title . '</h2>' . $sub_title . '</td>'
          . '<td width="20%" align="right" valign="top">As of ' . h($time) . '<br>' . h($date) . '</td>'
          . '</tr><tr><td colspan="3" align="center" width="100%">'
          . '<table><tr><td style="text-align:left;">';
    return $html;
}

function page_footer(): string
{
    return '</td></tr></table></td></tr></table></body></html>';
}

// --- Reference text ---

function get_category_defs(): string
{
    return '<b style="color:blue;">USATF Membership codes:</b><br>'
         . 'AT - Athlete<br>DA - Disabled Athlete<br>'
         . 'CH - Coach, Uncertified<br>CD - Coach, Developmental certified<br>'
         . 'C1 - Coach, Level 1 certified<br>C2 - Coach, Level 2 certified<br>'
         . 'C3 - Coach, Level 3 certified<br>PA - Parent<br>'
         . 'OF - Official, Uncertified<br>OA - Official, Association<br>'
         . 'ON - Official, National<br>OM - Official, Master<br>'
         . 'AD - Administrator<br>FN - FAN';
}

function get_age_explain(): string
{
    return "<div style=\"text-align:center;\"><b style=\"color:blue;\">Age Column:</b></div>\n"
         . "If the age is followed by a 'v'<br>\n"
         . "then the date of birth has been<br>\nverified, otherwise it has not.<br>\n"
         . "Examples:<br>\n"
         . "&nbsp;&nbsp;27v&nbsp;-- DOB Verified.<br>\n"
         . "&nbsp;&nbsp;34&nbsp;&nbsp;-- DOB not verified.<br>\n";
}

function member_footer_note(?PDO $pdo): string
{
    $year = $pdo !== null ? get_db_param($pdo, 'membershipYear') : '';
    return '<a name="page_bottom"></a>'
         . '* - Memberships shown above are valid for ' . h($year) . '. Some of the application<br>'
         . 'dates may be a year or more in the past. This is because these<br>'
         . 'members renewed early or signed up for multi-year memberships.';
}

// --- Option_information class (form state + query building) ---

class Option_information
{
    /** @var array<string, string> */
    public $form_values = [];
    public $club_name = '';
    public $page_title = '';
    public $number_found = 0;
    public $mysql_age = "(YEAR(CURDATE())-YEAR(m.birth_date)) - (RIGHT(CURDATE(),5)<RIGHT(m.birth_date,5))";

    /** @var array<string, array> */
    public $form_vars = [
        'age'              => ['both', 'adult', 'youth'],
        'sex'              => ['both', 'male', 'female'],
        'club'             => ['none', 'unat', 'invalid', '#^[1-9]\d*$#'],
        'sort'             => ['lname', 'fname', 'age', 'city', 'sex', 'club', 'usatf_no', 'reg_date'],
        'sdir'             => ['up', 'down'],
        'search_method'    => ['no_search', 'like', 'exact'],
        'search_fname'     => ['none', '#.+#'],
        'search_lname'     => ['none', '#.+#'],
        'search_city'      => ['none', '#.+#'],
        'search_sex'       => ['both', 'male', 'female'],
        'search_age_from'  => ['none', '#\d+#'],
        'search_age_to'    => ['none', '#\d+#'],
        'search_club_name' => ['none', '#.+#'],
        'search_unattached' => ['all', 'member', 'unattached', 'invalid'],
        'page'             => ['1', 'all', '#\d+#'],
        'items_per_page'   => ['200', '#\d+#'],
    ];

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->get_form_values();
        if ($this->form_values['club'] !== 'none') {
            $this->load_club_name();
        }
    }

    public function default_value(string $key): bool
    {
        return $this->form_values[$key] === $this->form_vars[$key][0];
    }

    private function get_form_values(): void
    {
        foreach ($this->form_vars as $var => $possible) {
            if (isset($_REQUEST[$var]) && ($val = trim((string) $_REQUEST[$var])) !== '') {
                $last = $possible[count($possible) - 1];
                $is_regex = (bool) preg_match('/^#.+#$/', $last);
                if (!in_array($val, $possible, true) && !($is_regex && preg_match($last, $val))) {
                    die('<pre>Invalid value for ' . h($var) . '</pre>');
                }
            } else {
                $val = (string) $possible[0];
            }
            $this->form_values[$var] = $val;
        }
    }

    private function load_club_name(): void
    {
        $club = $this->form_values['club'];
        if ($club === 'unat') {
            $this->club_name = 'Unattached';
        } elseif ($club === 'invalid') {
            $this->club_name = 'Invalid';
        } else {
            $row = get_club_info($this->pdo, (int) $club);
            $this->club_name = $row ? make_lower_case_name((string) $row['club_name']) : '';
        }
    }

    public function make_search_description(): string
    {
        $fv = $this->form_values;
        if ($fv['search_method'] === 'no_search') {
            return '';
        }
        $found = [];
        if (!$this->default_value('search_fname'))     $found[] = "Fname=" . $fv['search_fname'];
        if (!$this->default_value('search_lname'))     $found[] = "Lname=" . $fv['search_lname'];
        if (!$this->default_value('search_city'))      $found[] = "city=" . $fv['search_city'];
        if (!$this->default_value('search_club_name')) $found[] = "club=" . $fv['search_club_name'];
        if (!$this->default_value('search_sex'))       $found[] = $fv['search_sex'];
        if (!$this->default_value('search_age_from') || !$this->default_value('search_age_to')) {
            if (!$this->default_value('search_age_from') && !$this->default_value('search_age_to')) {
                $found[] = "age " . $fv['search_age_from'] . " to " . $fv['search_age_to'];
            } elseif (!$this->default_value('search_age_from')) {
                $found[] = "age >= " . $fv['search_age_from'];
            } else {
                $found[] = "age <= " . $fv['search_age_to'];
            }
        }
        if (!$this->default_value('search_unattached')) {
            $found[] = $fv['search_unattached'] === 'member' ? 'club member' : $fv['search_unattached'];
        }
        return "Member search: " . implode(', ', $found);
    }

    /**
     * Build WHERE clause fragments and parameter bindings for search filters.
     * @return array{0: string[], 1: array}
     */
    public function get_search_filters(): array
    {
        $fv = $this->form_values;
        $clauses = [];
        $params = [];
        if ($fv['search_method'] === 'no_search') {
            return [$clauses, $params];
        }
        $like = $fv['search_method'] === 'like';

        if ($fv['search_fname'] !== 'none') {
            $clauses[] = $like ? "m.first_name LIKE :sf" : "m.first_name = :sf";
            $params['sf'] = $like ? $fv['search_fname'] . '%' : $fv['search_fname'];
        }
        if ($fv['search_lname'] !== 'none') {
            $clauses[] = $like ? "m.last_name LIKE :sl" : "m.last_name = :sl";
            $params['sl'] = $like ? $fv['search_lname'] . '%' : $fv['search_lname'];
        }
        if ($fv['search_city'] !== 'none') {
            $clauses[] = $like ? "m.city LIKE :sc" : "m.city = :sc";
            $params['sc'] = $like ? $fv['search_city'] . '%' : $fv['search_city'];
        }
        if ($fv['search_club_name'] !== 'none') {
            $clauses[] = $like ? "c.club_name LIKE :scn" : "c.club_name = :scn";
            $params['scn'] = $like ? $fv['search_club_name'] . '%' : $fv['search_club_name'];
        }
        if ($fv['search_sex'] !== 'both') {
            $clauses[] = "m.gender = :ssex";
            $params['ssex'] = $fv['search_sex'] === 'male' ? 'M' : 'F';
        }
        if ($fv['search_age_from'] !== 'none' && $fv['search_age_from'] !== '0') {
            $clauses[] = $this->mysql_age . " >= :age_from";
            $params['age_from'] = (int) $fv['search_age_from'];
        }
        if ($fv['search_age_to'] !== 'none') {
            $clauses[] = $this->mysql_age . " <= :age_to";
            $params['age_to'] = (int) $fv['search_age_to'];
        }
        if ($fv['search_unattached'] !== 'all') {
            if ($fv['search_unattached'] === 'invalid') {
                $clauses[] = "c.club_no IS NULL";
            } else {
                $op = $fv['search_unattached'] === 'member' ? '<>' : '=';
                $clauses[] = "m.club_affiliation {$op} 0";
            }
        }
        return [$clauses, $params];
    }

    /**
     * Build full WHERE clause string + params (search + category + club filters).
     * @return array{0: string, 1: array}
     */
    public function get_select_filters(): array
    {
        $fv = $this->form_values;
        [$clauses, $params] = $this->get_search_filters();

        if ($fv['age'] !== 'both') {
            $clauses[] = $fv['age'] === 'youth'
                ? $this->mysql_age . " < 18"
                : $this->mysql_age . " > 17";
        }
        if ($fv['sex'] !== 'both') {
            $clauses[] = "m.gender = :fsex";
            $params['fsex'] = $fv['sex'] === 'male' ? 'M' : 'F';
        }
        if ($fv['club'] !== 'none') {
            if ($fv['club'] === 'invalid') {
                $clauses[] = "c.club_no IS NULL";
            } elseif ($fv['club'] === 'unat') {
                $clauses[] = "m.club_affiliation = 0";
            } else {
                $clauses[] = "m.club_affiliation = :fclub";
                $params['fclub'] = (int) $fv['club'];
            }
        }
        $where = $clauses ? " AND " . implode(" AND ", $clauses) : '';
        return [$where, $params];
    }

    public function get_page_number(): string
    {
        $page = $this->form_values['page'];
        $ipp = (int) $this->form_values['items_per_page'];
        if ($page !== 'all' && ((int) $page - 1) * $ipp > $this->number_found) {
            $page = (string) ((int) floor($this->number_found / $ipp) + 1);
        }
        return $page;
    }

    /**
     * Count total rows and return LIMIT clause string.
     */
    public function get_page_clause(string $where_extra, array $params): string
    {
        $sql = "SELECT COUNT(*) AS cnt FROM pa_members m"
             . " LEFT JOIN roster_counts r ON m.club_affiliation = r.club_no"
             . " LEFT JOIN tblCLUBS c ON m.club_affiliation = c.club_no"
             . " WHERE 1=1 " . $where_extra;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->number_found = (int) $stmt->fetchColumn();

        $ipp = (int) $this->form_values['items_per_page'];
        $page = $this->get_page_number();
        if ($this->number_found <= $ipp || $page === 'all') {
            return '';
        }
        $start = ((int) $page - 1) * $ipp;
        return "LIMIT {$start}, {$ipp}";
    }

    public function make_pagination(): string
    {
        $ipp = (int) $this->form_values['items_per_page'];
        if ($this->number_found <= $ipp) {
            return '';
        }
        $page = $this->get_page_number();
        if ($page === 'all') {
            $link = $this->make_option_link('page', '1', 'paginate', 'Paginate');
            return " Displaying all ({$link}).";
        }
        $p = (int) $page;
        $start = ($p - 1) * $ipp + 1;
        $end = min($start + $ipp - 1, $this->number_found);
        $total_pages = (int) ceil($this->number_found / $ipp);
        $links = [];
        if ($p > 2)            $links[] = $this->make_option_link('page', '1', '<b>&lt;</b>', 'First page');
        if ($p > 1)            $links[] = $this->make_option_link('page', (string)($p - 1), '&lt;', 'Previous page');
        $links[] = "page {$p} of {$total_pages}";
        if ($p < $total_pages) $links[] = $this->make_option_link('page', (string)($p + 1), '&gt;', 'Next page');
        if ($p < $total_pages - 1) $links[] = $this->make_option_link('page', (string)$total_pages, '<b>&gt;</b>', 'Last page');
        $links[] = $this->make_option_link('page', 'all', 'all', 'Show all');
        return " Displaying {$start} to {$end} (" . implode(' ', $links) . ")";
    }

    public function get_order_clause(): string
    {
        $fv = $this->form_values;
        switch ($fv['sort']) {
            case 'lname':    $fields = ['m.last_name', 'm.first_name']; break;
            case 'fname':    $fields = ['m.first_name', 'm.last_name']; break;
            case 'age':      $fields = ['(NOW() - m.birth_date)']; break;
            case 'sex':      $fields = ['m.gender', 'm.last_name', 'm.first_name']; break;
            case 'city':     $fields = ['m.city', 'm.last_name', 'm.first_name']; break;
            case 'usatf_no': $fields = ['membership_number']; break;
            case 'reg_date': $fields = ['(NOW() - m.date_applied)']; break;
            case 'club':     $fields = ['c.club_name', 'm.last_name', 'm.first_name']; break;
            default: die('Invalid sort');
        }
        if ($fv['sdir'] === 'down') {
            $fields[0] .= ' DESC';
        }
        return "ORDER BY " . implode(', ', $fields);
    }

    public function make_category_description(): string
    {
        $cats = [];
        foreach (['age', 'sex'] as $key) {
            if (!$this->default_value($key)) {
                $cats[] = $this->form_values[$key];
            }
        }
        return implode(' ', $cats);
    }

    public function make_category_title(string $cat_desc): string
    {
        $case = ($this->club_name !== '' ? 2 : 0) + ($cat_desc !== '' ? 1 : 0) + 1;
        switch ($case) {
            case 1: return 'All members';
            case 2: return $cat_desc . ' members';
            case 3: return $this->club_name . ' roster';
            case 4: return $this->club_name . ' (' . $cat_desc . ') roster';
            default: return 'Members';
        }
    }

    public function make_page_title(): string
    {
        $search = $this->make_search_description();
        $cat = $this->make_category_description();
        if ($search) {
            return $cat ? "{$search}; {$cat}" : $search;
        }
        return ucfirst($this->make_category_title($cat));
    }

    // --- Link builders ---

    public function make_self_link(string $tag, string $display, string $title = ''): string
    {
        $parms = [];
        foreach ($this->form_values as $var => $val) {
            if ($this->form_vars[$var][0] !== $val) {
                $parms[] = h($var) . '=' . urlencode($val);
            }
        }
        $qs = $parms ? '?' . implode('&amp;', $parms) : '';
        $tag_part = $tag ? '#' . h($tag) : '';
        $t = $title ? ' title="' . h($title) . '"' : '';
        return '<a href="' . h(this_script_name()) . $qs . $tag_part . '"' . $t . '>' . $display . '</a>';
    }

    public function make_option_link(string $changing_var, string $new_value, string $fixed_title = '', string $hover = ''): string
    {
        $current = $this->form_values[$changing_var];
        $title = $fixed_title !== '' ? $fixed_title : ucfirst($new_value);
        if ($new_value === $current && $changing_var !== 'sort') {
            return "<strong>{$title}</strong>";
        }
        $parms = [];
        if ($new_value === $current && $changing_var === 'sort') {
            if ($this->form_values['sdir'] === 'up') {
                $title .= '&nbsp;&uarr;';
                $parms[] = 'sdir=down';
            } else {
                $title .= '&nbsp;&darr;';
            }
        }
        foreach ($this->form_values as $var => $val) {
            if ($var === 'sdir') continue;
            if ($var === $changing_var) $val = $new_value;
            if ($this->form_vars[$var][0] !== $val) {
                $parms[] = h($var) . '=' . urlencode($val);
            }
        }
        $qs = $parms ? '?' . implode('&amp;', $parms) : '';
        $desc = $hover ? ' title="' . h($hover) . '"' : '';
        return '<a href="' . h(this_script_name()) . $qs . '"' . $desc . '>' . $title . '</a>';
    }
}

// --- Menu builders ---

function make_search_select(): string
{
    $cs = club_script_name();
    $ts = this_script_name();
    return '[ <a href="' . h($cs) . '">Club list</a>'
         . ' | <a href="' . h($ts) . '?age=adult">All adult</a>'
         . ' | <a href="' . h($ts) . '?age=youth">All youth</a> ]';
}

function make_menu_select(Option_information $oi): string
{
    $op_adult    = $oi->make_option_link('age', 'adult');
    $op_youth    = $oi->make_option_link('age', 'youth');
    $op_all_ages = $oi->make_option_link('age', 'both');
    $op_male     = $oi->make_option_link('sex', 'male');
    $op_female   = $oi->make_option_link('sex', 'female');
    $op_all_sex  = $oi->make_option_link('sex', 'both');
    $spacer = ' &nbsp; &nbsp; ';
    $menu = "[ {$op_male} | {$op_female} | {$op_all_sex} ]{$spacer}[ {$op_adult} | {$op_youth} | {$op_all_ages} ]";

    $ts = this_script_name();
    $cs = club_script_name();
    $search = '<a href="' . h($ts) . '?cmd=search">Search</a>';
    $club_no = $oi->form_values['club'];

    if ($club_no !== 'none' && $club_no !== 'unat') {
        $ci = '<a href="' . h($cs) . '?club_no=' . urlencode($club_no) . '">Club info</a>';
        $cl = '<a href="' . h($cs) . '">Club list</a>';
        $aa = '<a href="' . h($ts) . '?age=adult">All adult</a>';
        $ay = '<a href="' . h($ts) . '?age=youth">All youth</a>';
        $menu .= "{$spacer}[{$ci}|{$cl}|{$aa}|{$ay}|{$search}]";
    } elseif ($oi->form_values['search_method'] !== 'no_search' || $club_no === 'unat') {
        $cl = '<a href="' . h($cs) . '">Club list</a>';
        $aa = '<a href="' . h($ts) . '?age=adult">All adult</a>';
        $ay = '<a href="' . h($ts) . '?age=youth">All youth</a>';
        $menu .= "{$spacer}[{$cl}|{$aa}|{$ay}|{$search}]";
    } else {
        $cl = '<a href="' . h($cs) . '">Club list</a>';
        $menu .= "{$spacer}[{$cl}|{$search}]";
    }
    return $menu;
}

function member_header(Option_information $oi): string
{
    return "<center>" . make_menu_select($oi) . "</center><hr>\n";
}

// --- Main commands ---

function cmd_members(PDO $pdo): string
{
    $ts = this_script_name();
    $cs = club_script_name();
    $oi = new Option_information($pdo);
    $fv = $oi->form_values;

    [$where, $params] = $oi->get_select_filters();
    $order = $oi->get_order_clause();
    $limit = $oi->get_page_clause($where, $params);

    [$year, $month] = explode(' ', date('Y n'));
    $showing_renewals = (int) $month >= 11;

    $renewed_col = '';
    $next_year_header = '';
    if ($showing_renewals) {
        $renewed_col = "IF(m.next_years_number != '', 'Y', '') AS renewed,";
        $ny = (int) $year + 1;
        $dd = str_pad((string)($ny % 100), 2, '0', STR_PAD_LEFT);
        $explain = "'Y' after USATF# means<br>has {$ny} membership";
        $next_year_header = "<td class=\"popupbox\">{$dd}<a href=\"#\"><sup>?</sup><span>{$explain}</span></a></td>\n";
    }

    $applied_over_year_ago = "DATE_ADD(m.date_applied, INTERVAL 1 YEAR) < CURDATE()";
    $sql = "SELECT TRIM(CONCAT(m.first_name, ' ', m.middle_initial)) AS first_name,"
         . " TRIM(CONCAT(m.last_name, ' ', m.suffix)) AS last_name,"
         . " {$oi->mysql_age} AS age, DOB_Verified,"
         . " IF(m.membership_number != '', m.membership_number, m.next_years_number) AS membership_number,"
         . " {$renewed_col}"
         . " m.club_affiliation AS club_no, c.club_name, c.approved, r.roster_count,"
         . " m.city, m.gender, m.mem_categories,"
         . " DATE_FORMAT(m.date_applied, '%m/%d/%y') AS date_applied,"
         . " {$applied_over_year_ago} AS applied_over_year_ago"
         . " FROM pa_members m"
         . " LEFT JOIN roster_counts r ON m.club_affiliation = r.club_no"
         . " LEFT JOIN tblCLUBS c ON m.club_affiliation = c.club_no"
         . " WHERE 1=1 {$where} {$order} {$limit}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $club_specified = $fv['club'] !== 'none' && $fv['club'] !== 'invalid';
    $club_heading = '';
    if (!$club_specified) {
        $sort_club = $oi->make_option_link('sort', 'club', 'Club');
        $club_heading = "<td>{$sort_club}</td>\n";
    }

    $sort_fname   = $oi->make_option_link('sort', 'fname', 'First');
    $sort_lname   = $oi->make_option_link('sort', 'lname', 'Last');
    $sort_sex     = $oi->make_option_link('sort', 'sex', 'Sex');
    $sort_age     = $oi->make_option_link('sort', 'age', 'Age');
    $sort_city    = $oi->make_option_link('sort', 'city', 'City');
    $sort_usatf   = $oi->make_option_link('sort', 'usatf_no', 'USATF#');
    $sort_regdate = $oi->make_option_link('sort', 'reg_date', 'Registered');

    $age_explain_link = "<a href=\"#\" onMouseOver=\"open_window('ageDiv')\"><sup>?</sup></a>";
    $cat_explain_link = "<a href=\"#\" onMouseOver=\"open_window('categoryDiv')\"><sup>?</sup></a>";
    $sort_age .= $age_explain_link;

    $need_footnote = false;
    $table_body = '';
    $number_found = $oi->number_found;

    while ($row = $stmt->fetch()) {
        if ($row['applied_over_year_ago']) {
            $need_footnote = true;
        }
        if ($club_specified) {
            if (strtoupper((string)$row['approved']) !== 'Y' && $row['club_no'] != 0) {
                $number_found = 0;
                break;
            }
            $club_column = '';
        } else {
            $cno = (int) $row['club_no'];
            if (!$cno) {
                $club_field = '&nbsp;';
            } else {
                $cname = $row['club_name'] !== '' && $row['club_name'] !== null
                    ? get_lower_case_club_name($cno, (string) $row['club_name'])
                    : (string) $cno;
                if (strtoupper((string)$row['approved']) !== 'Y') {
                    $club_field = h($cname) . '<a href="' . h($cs) . '?club_no=' . $cno . '">?</a>';
                } else {
                    $name_link = '<a href="' . h($cs) . '?club_no=' . $cno . '">' . h($cname) . '</a>';
                    $roster_link = '<a href="' . h($ts) . '?club=' . $cno . '">' . h((string) $row['roster_count']) . '</a>';
                    $club_field = "{$name_link}&nbsp;({$roster_link})";
                }
            }
            $club_column = "<td>{$club_field}</td>";
        }

        $renewal_col_html = $showing_renewals ? '<td>' . h((string) $row['renewed']) . '</td>' : '';
        $age_display = $row['DOB_Verified'] === 'verified'
            ? h((string) $row['age']) . 'v'
            : h((string) $row['age']);

        $table_body .= '<tr>'
            . '<td>' . h((string) $row['first_name']) . '</td>'
            . '<td>' . h((string) $row['last_name']) . '</td>'
            . '<td>' . h((string) $row['gender']) . '</td>'
            . '<td>' . h((string) $row['city']) . '</td>'
            . '<td>' . $age_display . '</td>'
            . '<td>' . h((string) $row['membership_number']) . '</td>'
            . $renewal_col_html
            . '<td>' . h((string) $row['mem_categories']) . '</td>'
            . $club_column
            . '<td>' . h((string) $row['date_applied']) . '</td>'
            . "</tr>\n";
    }

    $footnote = $need_footnote
        ? '&nbsp;' . $oi->make_self_link('page_bottom', '*', 'See note at bottom of page.')
        : '';

    $renewal_heading = $showing_renewals ? $next_year_header : '';

    $member_list = make_popup_div('categoryDiv', get_category_defs())
        . make_popup_div('ageDiv', get_age_explain())
        . "<center>\n<table border=0 cellspacing=1 cellpadding=1>\n<thead>\n<tr>\n"
        . "<td>{$sort_fname}</td>\n<td>{$sort_lname}</td>\n<td>{$sort_sex}</td>\n"
        . "<td>{$sort_city}</td>\n<td>{$sort_age}</td>\n"
        . "<td style=\"text-align:center;\">{$sort_usatf}</td>\n"
        . $renewal_heading
        . "<td>Category{$cat_explain_link}</td>\n"
        . $club_heading
        . "<td>{$sort_regdate}{$footnote}</td>\n"
        . "</tr>\n</thead>\n<tbody>{$table_body}</tbody></table>\n"
        . ($need_footnote ? member_footer_note($pdo) : '')
        . "</center>\n";

    $page_title = $oi->make_page_title();
    $sub_title = "{$number_found} found." . $oi->make_pagination();

    return page_header($page_title, $sub_title, $pdo)
         . member_header($oi)
         . ($number_found > 0 ? $member_list : '<center><strong>No records found</strong></center>')
         . page_footer();
}

function cmd_search(): string
{
    $ts = this_script_name();
    $form = '<center>' . make_search_select() . '<br>'
        . '<form method="post" action="' . h($ts) . '" name="search_form">'
        . '<table>'
        . '<tr><td>First name</td><td>Last name</td><td>City</td><td>Club</td></tr>'
        . '<tr><td><input size="8" name="search_fname"></td>'
        . '<td><input size="8" name="search_lname"></td>'
        . '<td><input size="8" name="search_city"></td>'
        . '<td><input size="8" name="search_club_name"></td></tr>'
        . '<tr><td colspan=4>Search method: '
        . '<input type="radio" name="search_method" value="like" checked>Starts with | '
        . '<input type="radio" name="search_method" value="exact">Exact match</td></tr>'
        . '<tr><td colspan=4>Sex: '
        . '<input type="radio" name="search_sex" value="male">Male | '
        . '<input type="radio" name="search_sex" value="female">Female | '
        . '<input type="radio" name="search_sex" value="both" checked>Both</td></tr>'
        . '<tr><td colspan=4>Age: <input size=2 name="search_age_from"> to <input size=2 name="search_age_to"></td></tr>'
        . '<tr><td colspan=4>Club: '
        . '<input type="radio" name="search_unattached" value="member">Member | '
        . '<input type="radio" name="search_unattached" value="unattached">Unattached | '
        . '<input type="radio" name="search_unattached" value="invalid">Invalid | '
        . '<input type="radio" name="search_unattached" value="all" checked>All</td></tr>'
        . '<tr><td colspan=4 align="center">'
        . '<input type="SUBMIT" value="Search" style="font-family:Arial;font-size:8pt"> &nbsp; '
        . '<input type="reset" value="Reset" name="B1" style="font-family:Arial;font-size:8pt"></td></tr>'
        . '</table></form></center>';

    return page_header('Search members') . $form . page_footer();
}

function cmd_unavailable(): string
{
    return page_header('PA USATF Members')
         . '<center><p>Member data is temporarily unavailable. The membership database '
         . 'is being migrated. Please check back later or contact '
         . '<a href="http://www.pausatf.org/data/pacontacts.html">PA USATF</a> for assistance.</p></center>'
         . page_footer();
}

// --- Entry point ---

$cmd = isset($_REQUEST['cmd']) && $_REQUEST['cmd'] === 'search' ? 'search' : 'members';

if ($pdo === null) {
    echo cmd_unavailable();
} elseif ($cmd === 'search') {
    echo cmd_search();
} else {
    echo cmd_members($pdo);
}
