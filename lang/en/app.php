<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'dashboard'       => 'Dashboard',
        'users'           => 'Users',
        'active_sessions' => 'Active Sessions',
        'speed_profiles'  => 'Speed Profiles',
        'workspaces'      => 'Workspaces',
        'bookings'        => 'Bookings',
        'shared_sessions' => 'Shared Sessions',
        'settings'        => 'Settings',
        'my_profile'      => 'My Profile',
        'logout'          => 'Logout',
        'plans'           => 'Plans',
        'financial'       => 'Financial',
        'owners'          => 'Owners',
        'features'        => 'Features',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'linkspace'                 => 'Link Space Panel',
        'coworking_management'       => 'Coworking Internet Management',
        'email'                     => 'Email',
        'password'                  => 'Password',
        'sign_in'                   => 'Sign in',
        'dont_have_account'         => "Don't have an account?",
        'register'                  => 'Register',
        'create_your_account'       => 'Create your account',
        'account_information'       => 'Account Information',
        'name'                      => 'Name',
        'business_name'             => 'Business Name',
        'confirm_password'          => 'Confirm Password',
        'mikrotik_connection'       => 'MikroTik Router Connection',
        'router_ip'                 => 'Router IP Address',
        'api_port'                  => 'API Port',
        'username'                  => 'Username',
        'create_account'            => 'Create Account',
        'already_have_account'      => 'Already have an account?',
        'login'                     => 'Login',
        'admin_login'               => 'Admin Login',
        'admin_panel'               => 'Administrator Panel',
        'linkspace_admin'           => 'Link Space Panel Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common / CRUD
    |--------------------------------------------------------------------------
    */
    'common' => [
        'add'          => 'Add',
        'edit'         => 'Edit',
        'delete'       => 'Delete',
        'save'         => 'Save',
        'cancel'       => 'Cancel',
        'confirm'      => 'Confirm',
        'search'       => 'Search',
        'clear'        => 'Clear',
        'filter'       => 'Filter',
        'view'         => 'View',
        'back'         => 'Back',
        'close'        => 'Close',
        'create'       => 'Create',
        'update'       => 'Update',
        'select'       => 'Select',
        'actions'      => 'Actions',
        'status'       => 'Status',
        'date'         => 'Date',
        'time'         => 'Time',
        'duration'     => 'Duration',
        'total'        => 'Total',
        'notes'        => 'Notes',
        'description'  => 'Description',
        'name'         => 'Name',
        'phone'        => 'Phone',
        'email'        => 'Email',
        'address'      => 'Address',
        'city'         => 'City',
        'price'        => 'Price',
        'rate'         => 'Rate',
        'hours'        => 'Hours',
        'minutes'      => 'Minutes',
        'members'      => 'Members',
        'plan'         => 'Plan',
        'active'       => 'Active',
        'inactive'     => 'Inactive',
        'all'          => 'All',
        'total_revenue' => 'Total Revenue',
        'yes'          => 'Yes',
        'no'           => 'No',
        'or'           => 'or',
        'per_hour'     => 'per hour',
        'slash_hr'     => '/ hr',
        'slash_month'  => '/ month',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status labels
    |--------------------------------------------------------------------------
    */
    'status' => [
        'active'            => 'Active',
        'inactive'          => 'Inactive',
        'pending'           => 'Pending',
        'confirmed'         => 'Confirmed',
        'completed'         => 'Completed',
        'cancelled'         => 'Cancelled',
        'open'              => 'Open',
        'closed'            => 'Closed',
        'expired'           => 'Expired',
        'disabled'          => 'Disabled',
        'available'         => 'Available',
        'unavailable'       => 'Unavailable',
        'enabled'           => 'Enabled',
        'globally_disabled' => 'Globally Disabled',
        'never_activated'   => 'Never Activated',
        'expiring_soon'     => 'Expiring Soon',
    ],

    /*
    |--------------------------------------------------------------------------
    | Empty states
    |--------------------------------------------------------------------------
    */
    'empty' => [
        'no_users'            => 'No users yet. Add your first user.',
        'no_workspaces'       => 'No workspaces yet. Create your first workspace.',
        'no_rooms'            => 'No rooms in this workspace yet.',
        'no_bookings'         => 'No bookings found.',
        'no_active_sessions'  => 'No active sessions right now.',
        'no_open_sessions'    => 'No open sessions.',
        'no_shared_rooms'     => 'No shared rooms available.',
        'no_owners'           => 'No owners found.',
        'no_owners_yet'       => 'No owners yet.',
        'no_features'         => 'No features enabled yet. Contact your administrator.',
        'no_subscriptions'    => 'No subscription records.',
        'no_renewals'         => 'No renewals yet.',
        'no_owner_users'      => 'No users found for this owner.',
        'no_bookings_on_day'  => 'No bookings on this day.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */
    'btn' => [
        'add_user'           => 'Add New User',
        'add_workspace'      => 'Add Workspace',
        'add_plan'           => 'Add Plan',
        'add_owner'          => 'Add Owner',
        'add_room'           => 'Add Room',
        'add_profile'        => 'Add Profile',
        'create_workspace'   => 'Create Workspace',
        'create_owner'       => 'Create Owner',
        'create_plan'        => 'Create Plan',
        'create_profile'     => 'Create Profile',
        'update_workspace'   => 'Update Workspace',
        'update_plan'        => 'Update Plan',
        'update_profile'     => 'Update Profile',
        'update_user'        => 'Update User',
        'renew_subscription'  => 'Renew Subscription',
        'close_session'      => 'Close Session',
        'open_new_session'   => 'Open New Session',
        'open_session'       => 'Open Session',
        'confirm_booking'    => 'Confirm Booking',
        'confirm_save'       => 'Confirm & Save',
        'update_booking'     => 'Update Booking',
        'test_connection'    => 'Test Connection',
        'start_free_trial'   => 'Start Free Trial',
        'see_how_it_works'   => 'See How It Works',
        'get_started'        => 'Get Started',
        'request_demo'       => 'Request a Demo',
        'send_request'       => 'Send Request',
        'mark_completed'     => 'Mark Completed',
        'cancel_booking'     => 'Cancel Booking',
        'delete_booking'     => 'Delete Booking',
        'deactivate'         => 'Deactivate',
        'activate'           => 'Activate',
        'disable'            => 'Disable',
        'enable'             => 'Enable',
        'set_as_default'     => 'Set as Default',
        'view_reports'       => 'View Reports',
        'refresh'            => 'Refresh',
        'filter'             => 'Filter',
        'clear_filters'      => 'Clear Filters',
        'calendar_view'      => 'Calendar View',
        'list_view'          => 'List View',
        'search_name_phone'  => 'Search by name or phone...',
        'search'             => 'Search',
        'back_to_bookings'   => 'Back to Bookings',
        'back_to_workspaces' => 'Back to Workspaces',
        'back_to_shared_sessions' => 'Back to Shared Sessions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table headers
    |--------------------------------------------------------------------------
    */
    'table' => [
        'th' => [
            'name'        => 'Name',
            'phone'       => 'Phone',
            'email'       => 'Email',
            'download'    => 'Download',
            'upload'      => 'Upload',
            'status'      => 'Status',
            'created'     => 'Created',
            'actions'     => 'Actions',
            'date'        => 'Date',
            'time'        => 'Time',
            'user'        => 'User',
            'room'        => 'Room',
            'workspace'   => 'Workspace',
            'owner'       => 'Owner',
            'hours'       => 'Hours',
            'total'       => 'Total',
            'business'    => 'Business',
            'expires'     => 'Expires',
            'months'      => 'Months',
            'starts_at'   => 'Starts At',
            'expires_at'  => 'Expires At',
            'notes'       => 'Notes',
            'renewed_by'  => 'Renewed By',
            'plan'        => 'Plan',
            'price_month' => 'Price / Month',
            'renewals'    => 'Renewals',
            'revenue'     => 'Revenue',
            'active_now'  => 'Active Now',
            'amount'      => 'Amount',
            'by'          => 'By',
            'duration'    => 'Duration',
            'rate'        => 'Rate',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    */
    'placeholder' => [
        'search_name_phone' => 'Search by name or phone...',
        'full_name'         => 'Your full name',
        'email'             => 'you@example.com',
        'phone'             => '+20 100 000 0000',
        'business_name'     => 'Your coworking space name',
        'space_description' => 'Tell us about your space...',
        'router_ip'         => 'e.g. 192.168.88.1',
        'select_room'       => 'Select a room',
        'select_profile'    => 'Select a profile',
        'select_type'       => 'Select type...',
        'special_requests'  => 'Any special requests...',
        'renewal_note'      => 'Renewal note...',
        'notes_optional'    => 'Notes (optional)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages / Flash notifications
    |--------------------------------------------------------------------------
    */
    'msg' => [
        'user_added'             => 'User :name added successfully',
        'session_closed'         => 'Session closed. Total: :amount',
        'subscription_renewed'   => 'Subscription renewed: :plan plan — expires :date',
        'subscription_renewed_with_amount' => 'Subscription renewed: :plan plan for :months month(s). Amount: :amount',
        'free_plan_no_charge'    => 'Free plan — no charge.',
        'owner_created'          => 'Owner created successfully with :months-month subscription.',
        'owner_activated'        => 'Owner activated successfully.',
        'owner_deactivated'      => 'Owner deactivated successfully.',
        'plan_created'           => 'Plan created successfully.',
        'plan_updated'           => 'Plan updated successfully.',
        'plan_enabled'           => 'Plan enabled successfully.',
        'plan_disabled'          => 'Plan disabled successfully.',
        'user_updated'           => 'User updated successfully',
        'user_deleted'           => 'User deleted successfully',
        'user_status_updated'    => 'User status updated successfully',
        'speed_updated'          => 'Speed updated successfully',
        'plan_limit_reached'     => 'You have reached your plan limit of :max members. Please upgrade your plan to add more users.',
        'no_active_plan'         => 'No active plan assigned. Please contact your administrator.',
        'slots_remaining'        => 'Only :count slots remaining.',
        'plan_limit_reached_contact' => 'Plan limit reached. Contact your administrator to upgrade.',
        'no_default_profile'     => 'Please set a default speed profile first before adding users.',
        'mikrotik_error'         => 'MikroTik error: :message',
        'subscription_expires_in' => 'Your subscription expires in :days days. Please contact your administrator to renew.',
        'mikrotik_connection_error' => 'MikroTik connection error: :message',
        'check_mikrotik_settings' => 'Check your MikroTik connection in Settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */
    'label' => [
        'business_name'          => 'Business Name',
        'business'               => 'Business',
        'owner_name'             => 'Owner Name',
        'owner_email'            => 'Owner Email',
        'owner_info'             => 'Owner Information',
        'mikrotik_host'          => 'MikroTik Host',
        'mikrotik_port'          => 'MikroTik Port',
        'mikrotik_username'      => 'MikroTik Username',
        'mikrotik_password'      => 'MikroTik Password',
        'speed_download'         => 'Download Speed',
        'speed_upload'           => 'Upload Speed',
        'subscription'           => 'Subscription',
        'current_plan'           => 'Current Plan',
        'no_plan'                => 'No Plan',
        'members_used'           => 'Members Used',
        'expires_at'             => 'Expires At',
        'days_remaining'         => 'Days Remaining',
        'renew_subscription'     => 'Renew Subscription',
        'total_amount'           => 'Total Amount',
        'feature_access'         => 'Feature Access',
        'account_information'    => 'Account Information',
        'subscription_history'   => 'Subscription History',
        'recent_transactions'    => 'Recent Transactions',
        'monthly_revenue'        => 'Monthly Revenue',
        'revenue_by_plan'        => 'Revenue by Plan',
        'active_subscribers'     => 'Active Subscribers',
        'total_owners'           => 'Total Owners',
        'total_users'            => 'Total Users',
        'active_users'           => 'Active Users',
        'online_now'             => 'Online Now',
        'today_bookings'         => 'Today\'s Bookings',
        'pending_confirmations'  => 'Pending Confirmations',
        'open_sessions'          => 'Open Sessions',
        'this_month_revenue'     => 'This Month Revenue',
        'workspace_overview'     => 'Workspace Overview',
        'total_workspaces'       => 'Total Workspaces',
        'total_rooms'            => 'Total Rooms',
        'available_rooms'        => 'Available Rooms',
        'your_features'          => 'Your Features',
        'quick_links'            => 'Quick Links',
        'add_new_user'           => 'Add New User',
        'view_active_sessions'   => 'View Active Sessions',
        'manage_speed_profiles'  => 'Manage Speed Profiles',
        'booking_overview'       => 'Booking Overview',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin panel
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'admin_panel'          => 'Admin Panel',
        'admin_dashboard'      => 'Admin Dashboard',
        'total_owners'         => 'Total Owners',
        'active_subscriptions' => 'Active Subscriptions',
        'expiring_soon'        => 'Expiring Soon',
        'expired'              => 'Expired',
        'within_7_days'        => 'Within 7 Days',
        'total_workspaces'     => 'Total Workspaces',
        'total_rooms'          => 'Total Rooms',
        'total_hotspot_users'  => 'Total Hotspot Users',
        'across_all_owners'    => 'Across All Owners',
        'recent_renewals'      => 'Recent Renewals',
        'total_bookings'       => 'Total Bookings',
        'today_bookings'       => 'Today\'s Bookings',
        'monthly_revenue'      => 'Monthly Revenue',
        'owner_info'           => 'Owner Information',
        'subscription'         => 'Subscription',
        'renew_subscription'   => 'Renew Subscription',
        'feature_access'       => 'Feature Access',
        'global_features'      => 'Global Features',
        'feature_access_by_owner' => 'Feature Access by Owner',
        'feature'              => 'Feature',
        'key'                  => 'Key',
        'description'          => 'Description',
        'owners_using'         => 'Owners Using',
        'global_status'        => 'Global Status',
        'disable_globally'     => 'Disable Globally',
        'enable_globally'      => 'Enable Globally',
        'owner'                => 'Owner',
        'business'             => 'Business',
        'users_count'          => 'Users Count',
        'view_owner'           => 'View Owner',
        'add_owner'            => 'Add Owner',
        'edit_owner'           => 'Edit Owner',
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial
    |--------------------------------------------------------------------------
    */
    'financial' => [
        'total_revenue_all_time' => 'Total Revenue (All Time)',
        'this_month'             => 'This Month',
        'this_year'              => 'This Year',
        'active_subscribers'     => 'Active Subscribers',
        'monthly_revenue_chart'  => 'Monthly Revenue Chart',
        'revenue_by_plan'        => 'Revenue by Plan',
        'price_month'            => 'Price / Month',
        'renewals'               => 'Renewals',
        'revenue'                => 'Revenue',
        'active_now'             => 'Active Now',
        'subscriptions_expiring_14_days' => 'Subscriptions Expiring in 14 Days',
        'renew_link'             => 'Renew Link',
        'recent_transactions'    => 'Recent Transactions',
        'date'                   => 'Date',
        'owner'                  => 'Owner',
        'plan'                   => 'Plan',
        'months'                 => 'Months',
        'amount'                 => 'Amount',
        'expires'                => 'Expires',
        'by'                     => 'By',
        'no_plan'                => 'No Plan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    */
    'plan' => [
        'plans'          => 'Plans',
        'add_plan'       => 'Add Plan',
        'edit_plan'      => 'Edit Plan',
        'name'           => 'Name',
        'slug'           => 'Slug',
        'max_members'    => 'Max Members',
        'price_per_month' => 'Price Per Month',
        'sort_order'     => 'Sort Order',
        'create_plan'    => 'Create Plan',
        'update_plan'    => 'Update Plan',
        'up_to_members'  => 'Up to :count Members',
        'members'        => 'Members',
        'active_owners'  => 'Active Owners',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'my_profile'           => 'My Profile',
        'account_information'  => 'Account Information',
        'mikrotik_connection'  => 'MikroTik Connection',
        'current_plan'         => 'Current Plan',
        'no_plan'              => 'No Plan',
        'members_used'         => 'Members Used',
        'subscription'         => 'Subscription',
        'expires'              => 'Expires',
        'days_remaining'       => 'Days Remaining',
    ],

    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    */
    'workspace' => [
        'my_workspaces'          => 'My Workspaces',
        'add_workspace'          => 'Add Workspace',
        'create_workspace'       => 'Create Workspace',
        'edit_workspace'         => 'Edit Workspace',
        'update_workspace'       => 'Update Workspace',
        'workspace_name'         => 'Workspace Name',
        'description'            => 'Description',
        'address'                => 'Address',
        'city'                   => 'City',
        'phone'                  => 'Phone',
        'rooms'                  => 'Rooms',
        'add_room'               => 'Add Room',
        'edit_room'              => 'Edit Room',
        'room_name'              => 'Room Name',
        'type'                   => 'Type',
        'capacity'               => 'Capacity',
        'price_per_hour'         => 'Price Per Hour',
        'available'              => 'Available',
        'unavailable'            => 'Unavailable',
        'mark_available'         => 'Mark Available',
        'mark_unavailable'       => 'Mark Unavailable',
        'delete_workspace_confirm' => 'Are you sure you want to delete this workspace?',
        'delete_room_confirm'    => 'Are you sure you want to delete this room?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking
    |--------------------------------------------------------------------------
    */
    'booking' => [
        'bookings'             => 'Bookings',
        'new_booking'          => 'New Booking',
        'edit_booking'         => 'Edit Booking',
        'calendar_view'        => 'Calendar View',
        'list_view'            => 'List View',
        'booking_summary'      => 'Booking Summary',
        'user'                 => 'User',
        'room'                 => 'Room',
        'date'                 => 'Date',
        'start_time'           => 'Start Time',
        'end_time'             => 'End Time',
        'duration'             => 'Duration',
        'rate'                 => 'Rate',
        'total'                => 'Total',
        'notes'                => 'Notes',
        'confirm_booking'      => 'Confirm Booking',
        'update_booking'       => 'Update Booking',
        'cancel_booking'       => 'Cancel Booking',
        'delete_booking'       => 'Delete Booking',
        'mark_completed'       => 'Mark Completed',
        'checking_availability' => 'Checking Availability...',
        'room_available'       => 'Room is Available',
        'room_unavailable'     => 'Room is Unavailable',
        'all_statuses'         => 'All Statuses',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Sessions
    |--------------------------------------------------------------------------
    */
    'session' => [
        'shared_sessions'          => 'Shared Sessions',
        'open_new_session'         => 'Open New Session',
        'open_session'             => 'Open Session',
        'close_session'            => 'Close Session',
        'open_sessions'            => 'Open Sessions',
        'occupied'                 => 'Occupied',
        'user'                     => 'User',
        'phone'                    => 'Phone',
        'room'                     => 'Room',
        'workspace'                => 'Workspace',
        'date'                     => 'Date',
        'start'                    => 'Start',
        'duration'                 => 'Duration',
        'est_price'                => 'Est. Price',
        'calculating'              => 'Calculating...',
        'time'                     => 'Time',
        'rate'                     => 'Rate',
        'total'                    => 'Total',
        'cancel'                   => 'Cancel',
        'confirm_save'             => 'Confirm & Save',
        'how_shared_sessions_work' => 'How Shared Sessions Work',
        'session_open_instructions' => 'Select a user, room, and start time to open a new shared session.',
        'session_close_instructions' => 'Review the session details and confirm to close and calculate the total.',
        'room_capacity_note'       => 'This room can hold up to :capacity people at a time.',
        'failed_to_close_session'  => 'Failed to close session. Please try again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Users (Hotspot Users)
    |--------------------------------------------------------------------------
    */
    'user' => [
        'hotspot_users'          => 'Hotspot Users',
        'add_new_user'           => 'Add New User',
        'edit_user'              => 'Edit User',
        'update_user'            => 'Update User',
        'name'                   => 'Name',
        'phone'                  => 'Phone',
        'password'               => 'Password',
        'email'                  => 'Email',
        'notes'                  => 'Notes',
        'download_speed'         => 'Download Speed',
        'upload_speed'           => 'Upload Speed',
        'status'                 => 'Status',
        'created'                => 'Created',
        'user_info'              => 'User Information',
        'speed_profile'          => 'Speed Profile',
        'change_speed'           => 'Change Speed',
        'select_speed_profile'   => 'Select Speed Profile',
        'update_speed_on_mikrotik' => 'Update Speed on MikroTik',
        'recent_bookings'        => 'Recent Bookings',
        'phone_hint'             => 'Phone number cannot be changed after creation.',
        'speed_change_hint'      => 'Changing the speed will update the user on the MikroTik router.',
        'phone_cannot_be_changed' => 'Phone cannot be changed.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Speed Profiles
    |--------------------------------------------------------------------------
    */
    'speed' => [
        'speed_profiles'      => 'Speed Profiles',
        'add_profile'         => 'Add Profile',
        'create_profile'      => 'Create Profile',
        'edit_profile'        => 'Edit Profile',
        'update_profile'      => 'Update Profile',
        'name'                => 'Name',
        'download'            => 'Download',
        'upload'              => 'Upload',
        'default'             => 'Default',
        'users'               => 'Users',
        'set_as_default'      => 'Set as Default',
        'default_profile_note' => 'This profile is assigned to new users by default.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Landing / Marketing page
    |--------------------------------------------------------------------------
    */
    'landing' => [
        'product'                  => 'Product',
        'how_it_works'             => 'How It Works',
        'pricing'                  => 'Pricing',
        'sign_in'                  => 'Sign In',
        'get_started'              => 'Get Started',
        'now_with_smart_booking'   => 'Now with Smart Booking',
        'run_your_space'           => 'Run Your Coworking Space',
        'from_one_place'           => 'From One Place',
        'hero_description'         => 'Manage your coworking space, internet users, and room bookings all from a single dashboard.',
        'start_free_trial'         => 'Start Free Trial',
        'see_how_it_works'         => 'See How It Works',
        'no_cli_needed'            => 'No CLI Needed',
        'real_time_sync'           => 'Real-Time Sync',
        'five_minute_setup'        => '5-Minute Setup',
        'product_experience'       => 'Product Experience',
        'explore_the_system'       => 'Explore the System',
        'product_demo'             => 'Product Demo',
        'wifi_tab'                 => 'Wi-Fi',
        'rooms_tab'                => 'Rooms',
        'bookings_tab'             => 'Bookings',
        'simple_setup'             => 'Simple Setup',
        'go_live_in_three_steps'   => 'Go Live in 3 Simple Steps',
        'connect_your_router'      => 'Connect Your Router',
        'configure_your_space'     => 'Configure Your Space',
        'start_managing'           => 'Start Managing',
        'everything_at_a_glance'   => 'Everything at a Glance',
        'why_linkspace'            => 'Why Link Space Panel?',
        'built_for_spaces'         => 'Built for Coworking Spaces',
        'no_technical_skills'      => 'No Technical Skills Required',
        'real_time_everything'     => 'Real-Time Everything',
        'secure_multi_tenant'      => 'Secure & Multi-Tenant',
        'simple_transparent_pricing' => 'Simple & Transparent Pricing',
        'start_free_scale'         => 'Start Free, Scale as You Grow',
        'testimonials'             => 'Testimonials',
        'loved_by_space_operators' => 'Loved by Space Operators',
        'ready_to_transform'       => 'Ready to Transform Your Space?',
        'join_500_spaces'          => 'Join 500+ Coworking Spaces',
        'no_credit_card_needed'    => 'No Credit Card Needed',
        'footer_text'              => 'Link Space Panel — All-in-one coworking management platform.',
        'all_rights_reserved'      => 'All rights reserved.',
        'demo_modal_title'         => 'Request a Demo',
        'demo_modal_subtitle'      => 'Fill in the form and we\'ll get back to you within 24 hours.',
        'your_full_name'           => 'Your Full Name',
        'your_email'               => 'Your Email',
        'your_phone'               => 'Your Phone',
        'company_space_name'       => 'Company / Space Name',
        'message'                  => 'Message',
        'send_request'             => 'Send Request',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section titles
    |--------------------------------------------------------------------------
    */
    'section' => [
        'dashboard'       => 'Dashboard',
        'users'           => 'Users',
        'sessions'        => 'Active Sessions',
        'speed_profiles'  => 'Speed Profiles',
        'workspaces'      => 'Workspaces',
        'bookings'        => 'Bookings',
        'shared_sessions' => 'Shared Sessions',
        'settings'        => 'Settings',
        'profile'         => 'Profile',
        'features'        => 'Features',
        'plans'           => 'Plans',
        'financial'       => 'Financial',
        'owners'          => 'Owners',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notif' => [
        'title'           => 'Notifications',
        'mark_all_read'   => 'Mark all as read',
        'mark_read'       => 'Mark as read',
        'view_all'        => 'View all notifications',
        'empty'           => 'No notifications yet.',
        'unread_count'    => '{0}You\'re all caught up|{1}:count unread notification|[2,*]:count unread notifications',
        'all_marked_read' => 'All notifications marked as read.',
        'deleted'         => 'Notification deleted.',
        'delete_confirm'  => 'Delete this notification?',

        'gen' => [
            'sub_expiring_title'   => 'Subscription expiring soon',
            'sub_expiring_body'    => 'Your subscription expires in :days days. Contact your administrator to renew.',
            'sub_expired_title'    => 'Subscription expired',
            'sub_expired_body'     => 'Your subscription has expired. Please renew to keep using Link Space Panel.',
            'sub_renewed_title'    => 'Subscription renewed',
            'sub_renewed_body'     => 'Your :plan plan is active until :date.',
            'plan_limit_title'     => 'Member limit reached',
            'plan_limit_body'      => 'You have reached your plan limit of :max members. Upgrade to add more.',
            'booking_today_title'  => 'Booking today',
            'booking_today_body'   => ':room · :time',
            'booking_pending_title' => 'Booking awaiting confirmation',
            'booking_pending_body' => ':room on :date is still pending confirmation.',
        ],
    ],
];
