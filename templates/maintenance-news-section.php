<?php 
/**
 * Maintenance & News Section Template
 *
 * Variables made available by whmin_render_maintenance_news_section():
 * - $items (array): Array of news items to display initially (max $limit)
 * - $has_more (bool): True if more items exist beyond the initial limit
 * - $limit (int): The initial display limit (defaults to 6 for the 3-column layout)
 */
if (!defined('ABSPATH')) exit;

// We need an initial limit of 6 to show 3x2 grid (or 1 featured + 5 regular), 
// but the external function might still pass 5. We'll stick to a dynamic 
// approach where $limit is the actual initial amount shown.
// For a 1-featured + 5-regular layout, $limit should ideally be 6.
$initial_offset = count($items); 
$initial_limit_for_php_logic = $limit; // The value passed from whmin_render_maintenance_news_section (ideally 6)

// Split the first item as "featured"
$featured_item = array_shift($items); 
// The rest of the items will form the grid
$grid_items = $items;
?>
<style>
    /* 
     * ===============================================================
     * Stunning News Item Styles - Consistent with whmin-card styles
     * ===============================================================
     */
    .whmin-news-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3-column grid by default */
        gap: 1.5rem; 
    }
    
    /* === Base Styles for all Cards === */
    .whmin-news-item {
        border-radius: 10px;
        padding: 1.25rem;
        background: #ffffff;
        border: 1px solid #e9ecef; 
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        transition: background .25s ease, transform .2s ease, box-shadow .25s ease;
        /* FIX: Ensure content wraps correctly */
        word-wrap: break-word; 
        overflow-wrap: break-word;
    }
    
    /* === Featured (Latest) Post Style (Simple Green Neon Glow) === */
    .whmin-news-item.is-featured {
        grid-column: 1 / -1;
        padding: 2rem;
        background: #f8f9fa;
        border: 2px solid #075b63;
        transform: none;
        position: relative;
        z-index: 1;
        --neon-glow-color: #075b63;
        box-shadow: 0 0 5px var(--neon-glow-color), 0 0 5px var(--neon-glow-color) inset, 0 0 1px rgba(7, 91, 99, 0.4);
    }

    /* Remove unneeded pseudo-elements from simplified glow */
    .whmin-news-item.is-featured::before,
    .whmin-news-item.is-featured::after {
        content: none; 
    }

    .whmin-news-item.is-featured .whmin-news-title {
        font-size: 1.5rem; 
        margin-bottom: 0.5rem;
    }

    .whmin-news-item.is-featured .whmin-news-meta {
        font-size: 0.9rem;
        margin-bottom: 1rem;
        border-left-width: 3px;
    }
    
    .whmin-news-item.is-featured .whmin-news-body p {
        font-size: 1rem;
        color: #343a40;
    }

    /* === Regular Grid Post Styles === */
    .whmin-news-item:not(.is-featured):hover {
        background: #ffffff;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border-color: #e9ecef;
    }

    .whmin-news-link {
        text-decoration: none !important;
        color: inherit;
        display: block;
    }
    
    .whmin-news-link:hover {
        text-decoration: none !important; 
    }
    
    .whmin-news-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .whmin-news-impact-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.3rem 0.75rem; 
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    .whmin-news-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: #075b63;
        line-height: 1.3;
    }
    
    .whmin-news-meta {
        font-size: 0.75rem;
        color: #90959b;
        margin-bottom: 0.5rem;
        font-weight: 500;
        border-left: 2px solid #e9ecef;
        padding-left: 10px;
        display: block;
        line-height: 1;
    }
    
    .whmin-news-body p {
        margin: 0;
        font-size: 0.9rem;
        color: #495057; 
        line-height: 1.5;
    }

    /* === Responsive Adjustments === */
    @media (max-width: 992px) {
        .whmin-news-list {
            grid-template-columns: repeat(2, 1fr); /* 2 columns on tablet */
        }
    }
    @media (max-width: 768px) {
        .whmin-news-list {
            grid-template-columns: 1fr; /* 1 column on mobile */
            gap: 1rem;
        }
        .whmin-news-item.is-featured {
            padding: 1.5rem;
        }
        .whmin-news-item.is-featured .whmin-news-title {
            font-size: 1.25rem;
        }
    }

    /* 
     * ===============================================================
     * Load More Button and Spinner 
     * ===============================================================
     */
    .whmin-load-more-container {
        text-align: center;
        padding-top: 2rem;
    }
    .whmin-load-more-btn {
        background-color: #075b63; 
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 0.7rem 1.8rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 10px rgba(7, 91, 99, 0.2);
    }
    .whmin-load-more-btn:hover {
        background-color: #044348;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(7, 91, 99, 0.3);
    }
    .whmin-load-more-btn:disabled {
        background-color: #ced4da;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    .whmin-spinner {
        display: none;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid #fff;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        animation: whmin-spin 0.8s linear infinite;
    }
    .whmin-load-more-btn.loading .whmin-spinner {
        display: block;
    }
    .whmin-load-more-btn.loading .whmin-btn-text {
        visibility: hidden;
    }
    @keyframes whmin-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="whmin-card whmin-maintenance-news">
    <div class="whmin-card-header">
        <h3><?php _e('Maintenance & News', 'whmin'); ?></h3>
    </div>
    <div class="whmin-card-body">
        <?php if (empty($featured_item) && empty($grid_items)): ?>
            <div class="whmin-no-news">
                <i class="mdi mdi-information-outline"></i>
                <p><?php _e('No recent news or scheduled maintenance to report.', 'whmin'); ?></p>
            </div>
        <?php else: ?>
            <div class="whmin-news-list" id="whmin-news-list" data-offset="<?php echo esc_attr($initial_offset); ?>">
                
                <?php 
                // 1. Render Featured Item
                if (!empty($featured_item)): 
                ?>
                    <div class="whmin-news-item is-featured">
                        <a class="whmin-news-link" href="<?php echo esc_url($featured_item['permalink']); ?>">
                            <div class="whmin-news-header">
                                <span class="whmin-news-impact-badge"
                                        style="background-color: <?php echo esc_attr($featured_item['impact_color']); ?>;">
                                    <?php echo esc_html($featured_item['impact_label']); ?>
                                </span>
                                <h4 class="whmin-news-title">
                                    <?php echo esc_html($featured_item['title']); ?>
                                </h4>
                            </div>
                            <div class="whmin-news-meta">
                                <?php echo esc_html($featured_item['date']); ?>
                            </div>
                            <div class="whmin-news-body">
                                <p><?php echo esc_html($featured_item['excerpt']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php 
                endif; 
                
                // 2. Render Grid Items
                foreach ($grid_items as $item): 
                    // This uses the helper function from the original plan
                    whmin_render_single_news_item($item);
                endforeach; 
                ?>
            </div>

            <?php if ($has_more): ?>
                <div class="whmin-load-more-container">
                    <button class="whmin-load-more-btn" id="whmin-load-more-news-btn">
                        <span class="whmin-spinner"></span>
                        <span class="whmin-btn-text"><?php _e('Load More News', 'whmin'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var loadMoreBtn = document.getElementById('whmin-load-more-news-btn');
    var newsList = document.getElementById('whmin-news-list');
    
    // Check for global data from localization (done in WHMIN::enqueue_frontend_assets)
    var whminData = window.WHMIN_Public_Data || {};
    var ajaxurl = whminData.ajaxurl;
    var nonce = whminData.nonce;

    if (loadMoreBtn && newsList && ajaxurl && nonce) {
        loadMoreBtn.addEventListener('click', function() {
            // NOTE: The offset is now count($items) in PHP, which is correct for pagination.
            var offset = parseInt(newsList.getAttribute('data-offset') || 0);
            var button = this;

            // Set loading state
            button.classList.add('loading');
            button.disabled = true;

            var data = new FormData();
            data.append('action', 'whmin_load_more_news');
            data.append('offset', offset);
            data.append('nonce', nonce); 
            // The AJAX call will return the HTML for the next batch of regular grid items.

            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Append new items directly into the grid
                    newsList.insertAdjacentHTML('beforeend', result.data.html);

                    // Update offset
                    newsList.setAttribute('data-offset', result.data.new_offset);

                    // Handle 'has_more'
                    if (result.data.has_more) {
                        button.disabled = false;
                        button.classList.remove('loading');
                    } else {
                        // No more posts, hide the button
                        button.style.display = 'none';
                    }
                } else {
                    console.error('Error loading more news:', result.data);
                    button.disabled = false;
                    button.classList.remove('loading');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                button.disabled = false;
                button.classList.remove('loading');
            });
        });
    }
});
</script>