<?php
/**
 * Conecta Internet — funciones del tema.
 *
 * @package Conecta_Internet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conecta_internet_setup' ) ) {
	function conecta_internet_setup() {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'post-thumbnails' );

		register_nav_menus(
			array(
				'primary' => __( 'Menú principal', 'conecta-internet' ),
				'footer'  => __( 'Menú de pie de página', 'conecta-internet' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'conecta_internet_setup' );

if ( ! function_exists( 'conecta_internet_register_pattern_categories' ) ) {
	function conecta_internet_register_pattern_categories() {
		register_block_pattern_category(
			'conecta-internet',
			array( 'label' => __( 'Conecta Internet', 'conecta-internet' ) )
		);
	}
}
add_action( 'init', 'conecta_internet_register_pattern_categories' );
