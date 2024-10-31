<?php

namespace PhoenixFAH;

use WP_Widget;

class Widget extends WP_Widget
{
    /**
     * @var FoldingHomeStats
     */
    private FoldingHomeStats $foldingHomeStats;

    /**
     * Ph_Folding_Widget constructor.
     */
    public function __construct(FoldingHomeStats $foldingHomeStats)
    {
        $this->foldingHomeStats = $foldingHomeStats;
        parent::__construct(
            'phoenix_folding_widget',
            __( 'Phoenix Folding@Home Widget', 'ph_folding' ),
            [
                'description' => __( 'Display stats for your folding@home donor account or team', 'ph_folding' ),
                'classname' => 'ph_folding-widget'
            ]
        );
        add_action( 'wp_enqueue_scripts', [&$this, 'enqueue'], 101 ); //after register in main class
    }

    /**
     *
     */
    public function enqueue(): void
    {
        if ( is_active_widget( false, false, $this->id_base, true ) ) {
            wp_enqueue_style( 'phoenix-folding' );
        }
    }

    /**
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance): void
    {
        // Before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if ( !empty( $instance['id'] ) && !empty( $instance['type'] ) ) {

            $attributes = [
                'class' => $instance['class'],
                'show_widget_title' => !empty( $instance['show_widget_title'] ),
                'show_id' => !empty( $instance['show_id'] ),
                'show_rank' => !empty( $instance['show_rank'] ),
                'show_donor_teams_team_donors' => !empty( $instance['show_donor_teams_team_donors'] ),
                'show_team_donors' => !empty( $instance['show_donor_teams_team_donors'] ),
                'show_donor_teams' => !empty( $instance['show_donor_teams_team_donors'] ),
                'show_logo' => !empty( $instance['show_logo'] ),
                'show_tagline' => !empty( $instance['show_tagline'] )
            ];

            $title = apply_filters( 'widget_title', $instance['title'] );
            if ( !empty( $title ) && $attributes['show_widget_title'] ) {
                echo $args['before_title'] . $title . $args['after_title'];
            }
            echo $this->foldingHomeStats->tableFactory( $instance['id'], $instance['type'], $attributes )->render();
        }
        echo $args['after_widget'];
    }

    /**
     * @param array $instance
     * @return string
     */
    public function form($instance)
    {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Folding@Home Stats', 'ph_folding' );
        $type = isset( $instance['type'] ) ? $instance['type'] : '';
        if ( isset( $instance['id'] ) ) {
            $id = is_numeric( $instance['id'] ) ? absint( $instance['id'] ) : $instance['id'];
        } else {
            $id = 1;
        }

        $class = isset( $instance['class'] ) ? $instance['class'] : '';

        $type_options = [
            'user' => __( 'Donor', 'ph_folding' ),
            'team' => __( 'Team', 'ph_folding' )
        ];
        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'ph-folding' ); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/>
        </p>
        <?php $this->getCheckboxField( 'show_widget_title', 'Show Widget Title', $instance ); ?>
        <p>
            <label
                    for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Display donor or team', 'ph-folding' ); ?>
                :</label>
            <select id="<?php echo $this->get_field_id( 'type' ); ?>"
                    name="<?php echo $this->get_field_name( 'type' ); ?>">
                <option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
                <?php foreach ( $type_options as $option => $label ) : ?>
                    <option value="<?php echo $option; ?>" <?php selected( $type, $option ); ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'id' ); ?>"><?php _e( 'Team/donor ID. If showing a donor you can write their username', 'ph-folding' ); ?>
                :</label>
            <input class="regular-text" id="<?php echo $this->get_field_id( 'id' ); ?>"
                   name="<?php echo $this->get_field_name( 'id' ); ?>" type="text"
                   value="<?php echo $id; ?>" size="3"/>
        </p>
        <?php $this->getCheckboxField( 'show_id', 'Show donor/team numerical ID in table', $instance, false ); ?>
        <?php $this->getCheckboxField( 'show_rank', 'Show donor/team rank?', $instance ); ?>
        <?php $this->getCheckboxField( 'show_donor_teams_team_donors', 'Show donor\'s teams/team\'s donors?', $instance ); ?>
        <?php $this->getCheckboxField( 'show_logo', 'Show Top folding logo?', $instance ); ?>
        <?php $this->getCheckboxField( 'show_tagline', 'Show F@H tagline?', $instance ); ?>
        <p>
            <label
                    for="<?php echo $this->get_field_id( 'class' ); ?>"><?php _e( 'Table container CSS Class', 'ph-folding' ); ?>
                :</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'class' ); ?>"
                   name="<?php echo $this->get_field_name( 'class' ); ?>" type="text"
                   value="<?php echo esc_attr( $class ); ?>"/>
        </p>
        <?php
        return '';
    }

    /**
     * @param string $fieldName
     * @param string $label
     * @param array  $instance
     * @param bool   $checked
     */
    public function getCheckboxField(string $fieldName, string $label, array $instance, bool $checked = true): void
    {
        $id = $this->get_field_id( $fieldName );
        if ( isset( $instance[$fieldName] ) ) {
            $checked = $instance[$fieldName];
        }
        ?>
        <p>
            <input class="checkbox" type="checkbox"<?php checked( $checked ); ?>
                   id="<?php echo $id; ?>"
                   name="<?php echo $this->get_field_name( $fieldName ); ?>"/>
            <label
                    for="<?php echo $id; ?>"><?php _e( $label, 'ph-folding' ); ?>
                :</label>
        </p>
    <?php }

    /**
     * Updating widget replacing old instances with new
     * On shutdown action, query DB and/or API for stats and updateTransient to avoid slowing down visitor's screen when performing expensive operations
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance): array
    {
        $id = (!empty( $new_instance['id'] )) ? strip_tags( $new_instance['id'] ) : 1;
        $type = (!empty( $new_instance['type'] )) ? strip_tags( $new_instance['type'] ) : '';
        $this->foldingHomeStats->statFactory( $id, $type, );
        return [
            'title' => (!empty( $new_instance['title'] )) ? strip_tags( $new_instance['title'] ) : '',
            'show_widget_title' => isset( $new_instance['show_widget_title'] ) && $new_instance['show_widget_title'],
            'type' => $type,
            'id' => $id,
            'show_id' => isset( $new_instance['show_id'] ) && $new_instance['show_id'],
            'show_rank' => isset( $new_instance['show_rank'] ) && $new_instance['show_rank'],
            'show_donor_teams_team_donors' => isset( $new_instance['show_donor_teams_team_donors'] ) && $new_instance['show_donor_teams_team_donors'],
            'show_logo' => isset( $new_instance['show_logo'] ) && $new_instance['show_logo'],
            'show_tagline' => isset( $new_instance['show_tagline'] ) && $new_instance['show_tagline'],
            'class' => (!empty( $new_instance['class'] )) ? strip_tags( $new_instance['class'] ) : ''
        ];

    }
}