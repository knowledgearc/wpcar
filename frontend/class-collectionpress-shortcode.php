<?php
if (!defined('WPINC')) {
    die;
}

/**
 * Provides a short code renderer.
 */
class CollectionPress_ShortCode
{
    public function render($atts)
    {
        if (isset($atts["limit"])) {
            $this->limit= $atts["limit"];
        } else {
            $this->limit = 0;
        }

        if (isset($atts["author"]) ) {
            $this->get_items($atts["author"], $this->limit);
        }


        if (isset($atts['list']) && $atts['list']=="authors") {
            $this->get_authors($this->limit);
        }
        //~ else if ( isset($atts['list']) && $atts['list']=="items"
            //~ && isset($atts['author']) && $atts['author']!="" ) {
            //~ $this->get_items($atts["author"]);
        //~ }
    }

    public function get_items($author , $limit)
    {
        $options = collectionpress_settings();

        $args = array(
            'timeout'=>30,
            'user-agent'=>'CollectionPress; '.home_url()
        );
        $url = 'discover.json?q=author:"'.$author.'"';

        if ( $limit ) {
            $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
            if($paged==1){
                $start = 0;
            }else{
                $start = (($limit*$paged)-$limit);
            }
            $url .='&rows='.$limit.'&start='.$start;
        }

        $response = wp_remote_get($this->get_url($url), $args);
        
        $response = json_decode(wp_remote_retrieve_body($response));
        
        if (file_exists(locate_template('collectionpress/item_display.php'))) {
            include(locate_template('collectionpress/item_display.php'));
        } else {
            include(CP_TEMPLATE_PATH.'/collectionpress/item_display.php');
        }
    }

    public function get_authors($limit){
        $posts_per_page = $limit;

        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $author_results = new WP_Query(array(
                        "post_type"      =>"cp_authors",
                        "post_status"    =>"publish",
                        "orderby"        =>"modified",
                        "order"          =>"DESC",
                        "posts_per_page" =>$posts_per_page,
                        "cache_results"  => false,
                        "paged"          => $paged) );
        $found_posts =$author_results->found_posts;
        $total_pages =$author_results->max_num_pages;
        if ($author_results->have_posts()) :
            while ($author_results->have_posts()) : $author_results->the_post();
                
                if (file_exists(locate_template('collectionpress/author_display.php'))) {
                    include(locate_template('collectionpress/author_display.php'));
                } else {
                    include(CP_TEMPLATE_PATH.'/collectionpress/author_display.php');
                }
                
            endwhile; ?>
            <div class="pagination">
                <?php               
                    $big = 999999999; // need an unlikely integer
                    echo paginate_links( array(
                        'base'      =>str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format'    =>'?paged=%#%',
                        'prev_text' =>__('&laquo;'),
                        'next_text' =>__('&raquo;'),
                        'current'   =>max(1, get_query_var('paged')),
                        'total'     =>$total_pages
                    ) );
                ?>
            </div>
        <?php endif; ?>
        <?php
        //~ return ob_get_clean();
    }
    
    public function get_url($endpoint)
    {
        $options = collectionpress_settings();

        $url = $options['rest_url'];

        return $url."/".$endpoint;
    }
}
