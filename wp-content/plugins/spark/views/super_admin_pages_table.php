<?php

/* == NOTICE ===================================================================
 * Please do not alter this file. Instead: make a copy of the entire plugin, 
 * rename it, and work inside the copy. If you modify this plugin directly and 
 * an update is released, your changes will be lost!
 * ========================================================================== */



/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary. In this tutorial, we are
 * going to use the WP_List_Table class directly from WordPress core.
 *
 * IMPORTANT:
 * Please note that the WP_List_Table class technically isn't an official API,
 * and it could change at some point in the distant future. Should that happen,
 * I will update this plugin with the most current techniques for your reference
 * immediately.
 *
 * If you are really worried about future compatibility, you can make a copy of
 * the WP_List_Table class (file path is shown just below) to use and distribute
 * with your plugins. If you do that, just remember to change the name of the
 * class to avoid conflicts with core.
 *
 * Since I will be keeping this tutorial up-to-date for the foreseeable future,
 * I am going to work with the copy of the class provided in WordPress core.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}




/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for geneemail the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */
class Super_Admin_Pages_Table extends WP_List_Table {
    
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'plugin',     //singular name of the listed records
            'plural'    => 'plugins',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
    
    }

    function is_active($id){
        global $wpdb;
        $status = $wpdb->get_var("SELECT status FROM wp_spark_pages WHERE page_id='" . $id . "'");
        return $status;
    }

    function single_row( $item ) {
        $status = $this->is_active($item['ID']);
        if($status == 'Active')
            $status = 'active';
        else
            $status='';
        echo "<tr class='$status'>";
        echo $this->single_row_columns( $item );
        echo "</tr>\n";
    }

    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            case 'post_date':
            case 'status':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_post_title($item){
        //Build row actions
        global $wpdb;
        $status = $this->is_active($item['ID']);
        if($status=='Active')
        {
            $actions = array(
                'deactivate'      => sprintf('<a href="?page=%s&action=%s&item=%s">Network Deactivate</a>',$_REQUEST['page'],'deactivate',$item['ID']),
                'upload'    => sprintf('<a style="cursor:pointer" class="set_custom_images" id="upbtn-%s">Upload Image</a>',$item['ID']),
            );
        }
        else{
            $actions = array(
                'activate'      => sprintf('<a href="?page=%s&action=%s&item=%s">Network Activate</a>',$_REQUEST['page'],'activate',$item['ID']),
                'upload'    => sprintf('<a style="cursor:pointer" class="set_custom_images" id="upbtn-%s">Upload Image</a>',$item['ID']),
            );
        }
        $image = $wpdb->get_var("SELECT image FROM wp_spark_pages WHERE page_id='" . $item['ID'] . "'");
        //Return the title contents
        return sprintf('<img src="%1$s" class="spark-pages-avatar"/><div class="spark-pages-title">%2$s %3$s</div>',
            $image ? $image:'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcS8GikQJ4SjNowi37yU_TNhBxAamP_afG0hFaHXL7-m_64d4kQe',
            /*$1%s*/ $item['post_title'],
            /*$2%s*/ $this->row_actions($actions, true)
        );
    }


    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'post_title'     => 'Title',
            'post_date'  => 'Published Date',
            'status' => 'Status'
        );
        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'post_title'     => array('post_title',false),     //true means it's already sorted
            'post_date'  => array('post_date',false),
            'status' => array('status', false)
        );
        return $sortable_columns;
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'activate'    => 'Activate',
            'deactivate' => 'Deactivate'
        );
        return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        // var_dump($_GET['plugin']);
        //Detect when a bulk action is being triggered...
        if(isset($_GET['plugin']) && is_array($_GET['plugin']))
            switch($this->current_action()){
                case 'activate':
                    foreach ($_GET['plugin'] as $plugin){
                        activate_spark_pages($plugin);
                    }
                    break;
                case 'deactivate':
                    foreach ($_GET['plugin'] as $plugin){
                        deactivate_spark_pages($plugin);
                    }
                    break;
                default:
                    break;
            }
        
    }


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 100;
        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        
        
        /** ************************************************************************
         * Normally we would be querying data from a database and manipulating that
         * for use in your list table. For this example, we're going to simplify it
         * slightly and create a pre-built array. Think of this as the data that might
         * be returned by $wpdb->query()
         * 
         * In a real-world scenario, you would make your own custom query inside
         * this class' prepare_items() method.
         * 
         * @var array 
         **************************************************************************/
        // $data = array();
        // $blog_id = get_current_blog_id();
        // $db_results = $wpdb->get_results("SELECT * FROM wp_spark_admin_pages WHERE blog_id='" . $blog_id. "'", ARRAY_A);
        // foreach($db_results as $item){
        //     $spark_page = $wpdb->get_results("SELECT * FROM wp_spark_pages WHERE ID='" . $item['spark_template_id'] ."'",ARRAY_A);
        //     $post_name = $wpdb->get_var("SELECT post_name FROM wp_" . $blog_id . "_posts WHERE ID='" . $item['post_id'] . "'");
        //     if($post_name)
        //         array_push($data, array(
        //             'ID' => $item['post_id'],
        //             'image' => $spark_page[0]['image'],
        //             'title' => $spark_page[0]['title'],
        //             'post_name' => $post_name,
        //         ));
        // }
        $data = array();
        $db_results = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_type='page' && post_status='publish'", ARRAY_A);
        foreach($db_results as $post){
            $status = $wpdb->get_var("SELECT status FROM wp_spark_pages WHERE page_id='" . $post['ID'] . "'");
            if($status != 'Active')
                $status='inActive';
            array_push($data, array(
                'ID' => $post['ID'],
                'post_title' => $post['post_title'],
                'post_date' => $post['post_date'],
                'status' => $status,
            ));
        }
        // $data = array(
        //     array(
        //         'ID'        => 1,
        //         'title'     => 'ericshields',
        //         'post_date'  => '2020-02-02 00:58:50'
        //     ),
        //     array(
        //         'ID'        => 2,
        //         'title'     => 'davidtendrich',
        //         'post_date'  => '2020-03-05 21:03:16'
        //     ),
        // );
                
        
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'status'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
        
        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        
                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }


}


/** *************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function render_super_admin_pages_table(){

    //Create an instance of our package class...
    $testListTable = new Super_Admin_Pages_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Spark Templates</h2>
        <!-- <p>
            <input type="number" value="" class="regular-text process_custom_images" id="process_custom_images" name="" max="" min="1" step="1">
            <button class="set_custom_images button">Set Image ID</button>
        </p> -->
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page']?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display()?>
        </form>
        
    </div>
    <?php
}

function activate_spark_pages($id){
   global $wpdb;
   $title = $wpdb->get_var("SELECT post_title FROM wp_posts WHERE ID='" . $id . "'");
   if($wpdb->get_var("SELECT * FROM wp_spark_pages WHERE page_id='" . $id . "'"))
        $wpdb->update("wp_spark_pages", array('status' => 'Active'), array('page_id' => $id));
    else
        $wpdb->insert("wp_spark_pages", array('page_id' => $id, 'status' => 'Active', 'title' => $title));
}
function deactivate_spark_pages($id){
    global $wpdb;
    $wpdb->update('wp_spark_pages', array('status' => 'inActive'), array('page_id' => $id));
}