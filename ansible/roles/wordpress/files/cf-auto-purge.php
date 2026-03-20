<?php
/**
 * Plugin Name: Cloudflare Auto-Purge on Save
 * Description: Purges Cloudflare cache for a page/post URL when content is updated.
 * Managed by Ansible - do not edit manually.
 */
add_action("save_post", function($post_id, $post) {
    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if ($post->post_status !== "publish") return;

    $token_file = "/etc/pausatf/cloudflare-api-token";
    $cf_key = file_exists($token_file) ? trim(file_get_contents($token_file)) : "";
    $zone = get_option("cloudflare_zone_id");

    if (empty($zone)) {
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $resp = wp_remote_get("https://api.cloudflare.com/client/v4/zones?name={$domain}", [
            "headers" => ["Authorization" => "Bearer {$cf_key}", "Content-Type" => "application/json"]
        ]);
        if (!is_wp_error($resp)) {
            $body = json_decode(wp_remote_retrieve_body($resp), true);
            if (!empty($body["result"][0]["id"])) {
                $zone = $body["result"][0]["id"];
                update_option("cloudflare_zone_id", $zone);
            }
        }
    }

    if (empty($cf_key) || empty($zone)) return;

    $url = get_permalink($post_id);
    if (empty($url)) return;

    wp_remote_post("https://api.cloudflare.com/client/v4/zones/{$zone}/purge_cache", [
        "headers" => ["Authorization" => "Bearer {$cf_key}", "Content-Type" => "application/json"],
        "body"    => json_encode(["files" => [$url]]),
    ]);
}, 99, 2);
