<?php

namespace Virtual_Optimizer;

class AutoPurge
{
    public static function init()
    {
        add_action('future_to_publish', [__CLASS__, 'on_post_published'], 10, 1);
        add_action('post_updated', [__CLASS__, 'on_post_updated'], 10, 3);
        add_action('wp_update_comment_count', [__CLASS__, 'on_comment_count_updated'], 10, 3);
    }

    public static function on_post_published($post)
    {
        if (!is_object($post)) {
            $post = get_post($post);
        }

        if (!$post) {
            return;
        }

        self::purge_related_urls($post->ID);
    }

    public static function on_post_updated($post_id, $post_after, $post_before)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post_before->post_status === 'auto-draft' || $post_before->post_status === 'draft') {
            return;
        }

        self::purge_related_urls($post_id);
    }

    public static function on_comment_count_updated($post_id, $new_comment_count, $old_comment_count)
    {
        self::purge_related_urls($post_id);
    }

    public static function purge_related_urls($post_id)
    {
        $urls = self::get_related_urls($post_id);

        if (!empty($urls)) {
            Purge::purge_urls($urls);
            Preload::preload_urls($urls, 11);
        }
    }

    public static function get_related_urls($post_id)
    {
        $urls = [];

        $permalink = get_permalink($post_id);
        if ($permalink) {
            $urls[] = $permalink;
        }

        $urls[] = home_url('/');

        $page_for_posts = get_option('page_for_posts');
        if ($page_for_posts) {
            $urls[] = get_permalink($page_for_posts);
        }

        $post_type = get_post_type($post_id);
        $post_type_archive = get_post_type_archive_link($post_type);
        if ($post_type_archive) {
            $urls[] = $post_type_archive;
        }

        $author_id = get_post_field('post_author', $post_id);
        $author_url = get_author_posts_url($author_id);
        if ($author_url) {
            $urls[] = $author_url;
        }

        $taxonomy_urls = self::get_post_taxonomy_urls($post_id);
        $urls = array_merge($urls, $taxonomy_urls);

        return array_unique($urls);
    }

    public static function get_post_taxonomy_urls($post_id)
    {
        $urls = [];
        $taxonomies = get_object_taxonomies(get_post_type($post_id), 'objects');
        $hierarchical = [];
        $non_hierarchical = [];

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->hierarchical) {
                $hierarchical[] = $taxonomy->name;
            } else {
                $non_hierarchical[] = $taxonomy->name;
            }
        }

        foreach ($non_hierarchical as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
            foreach ($terms as $term_id) {
                $term = get_term($term_id, $taxonomy);
                if (!$term || is_wp_error($term)) {
                    continue;
                }
                $term_url = get_term_link($term);
                if (!is_wp_error($term_url)) {
                    $urls[] = $term_url;
                }
            }
        }

        foreach ($hierarchical as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
            foreach ($terms as $term_id) {
                $term = get_term($term_id, $taxonomy);
                if (!$term || is_wp_error($term)) {
                    continue;
                }

                $term_url = get_term_link($term);
                if (!is_wp_error($term_url)) {
                    $urls[] = $term_url;
                }

                $parent_id = $term->parent;
                while ($parent_id) {
                    $parent_term = get_term($parent_id, $taxonomy);
                    if (!$parent_term || is_wp_error($parent_term)) {
                        break;
                    }
                    $parent_url = get_term_link($parent_term);
                    if (!is_wp_error($parent_url)) {
                        $urls[] = $parent_url;
                    }
                    $parent_id = $parent_term->parent;
                }
            }
        }

        return array_unique($urls);
    }
}
