<?php
/**
 * Plugin Name: Gigafibra · sitio
 * Description: SEO básico (meta título/descripción, Open Graph, datos estructurados) y pequeñas interacciones del sitio Gigafibra (selector de estrato, FAQ, pie de página móvil, contadores con movimiento reducido).
 * Author: Gigafibra
 * Version: 1.0.0
 *
 * Meta por página (campos personalizados):
 *   _gf_seo_title        Título SEO (etiqueta <title>)
 *   _gf_seo_description  Meta descripción
 *   _gf_seo_noindex      "1" para no indexar
 * Con Yoast SEO activo (instalado el 2026-10-28) este archivo NO imprime metas: Yoast gestiona título,
 * descripción, robots, Open Graph y schema; aquí solo se añaden teléfono/dirección al schema de Yoast.
 * Los campos _gf_seo_* se mantienen como respaldo si se desactiva Yoast.
 */

defined( 'ABSPATH' ) || exit;

function gf_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function gf_seo_post_id() {
	if ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
		return (int) get_option( 'page_on_front' );
	}
	return is_singular() ? (int) get_queried_object_id() : 0;
}

function gf_seo_description() {
	$id = gf_seo_post_id();
	$desc = $id ? get_post_meta( $id, '_gf_seo_description', true ) : '';
	if ( ! $desc ) { $desc = get_bloginfo( 'description' ); }
	return trim( wp_strip_all_tags( $desc ) );
}

// <title>
add_filter( 'pre_get_document_title', function ( $title ) {
	if ( gf_seo_plugin_active() ) { return $title; }
	$id = gf_seo_post_id();
	$custom = $id ? get_post_meta( $id, '_gf_seo_title', true ) : '';
	return $custom ? wp_strip_all_tags( $custom ) : $title;
}, 20 );
add_filter( 'document_title_separator', fn() => '|' );

// Per-page noindex (e.g. legal pages still waiting for their official text), merged into WP's robots meta.
add_filter( 'wp_robots', function ( $robots ) {
	if ( gf_seo_plugin_active() ) { return $robots; }
	$id = gf_seo_post_id();
	if ( $id && get_post_meta( $id, '_gf_seo_noindex', true ) ) {
		$robots['noindex'] = true;
		if ( empty( $robots['nofollow'] ) ) { $robots['follow'] = true; }
	}
	return $robots;
} );

// The theme prints the excerpt as description; we print our own.
add_filter( 'hello_elementor_description_meta_tag', '__return_false' );

add_action( 'wp_head', function () {
	if ( gf_seo_plugin_active() || is_admin() ) { return; }
	$id    = gf_seo_post_id();
	$desc  = gf_seo_description();
	$title = wp_get_document_title();
	$url   = $id ? ( is_front_page() ? home_url( '/' ) : get_permalink( $id ) ) : home_url( add_query_arg( [] ) );
	$img   = wp_get_attachment_image_url( 28, 'full' );

	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:locale" content="es_CO">' . "\n" );
	printf( '<meta property="og:type" content="%s">' . "\n", is_front_page() ? 'website' : 'article' );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) { printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) ); }
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	if ( $img ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
		echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="theme-color" content="#000000">' . "\n";

	if ( is_front_page() ) {
		$org = [
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'name' => 'Gigafibra S.A.S.',
			'alternateName' => 'Giga Fibra',
			'url' => home_url( '/' ),
			'logo' => wp_get_attachment_image_url( 25, 'full' ),
			'description' => 'Operador de telecomunicaciones autorizado por MinTIC. Internet 100% fibra óptica para hogares, empresas y conjuntos en Bogotá.',
			'telephone' => '+57 601 7425270',
			'address' => [
				'@type' => 'PostalAddress',
				'streetAddress' => 'Carrera 103b, 23G #15',
				'addressLocality' => 'Bogotá',
				'addressRegion' => 'Bogotá D.C.',
				'addressCountry' => 'CO',
			],
			'contactPoint' => [
				'@type' => 'ContactPoint',
				'telephone' => '+57 601 7425270',
				'contactType' => 'customer service',
				'areaServed' => 'CO',
				'availableLanguage' => 'es',
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	} elseif ( $id && is_page() ) {
		$crumbs = [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => [
				[ '@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => home_url( '/' ) ],
				[ '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title( $id ), 'item' => get_permalink( $id ) ],
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $crumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 1 );

// Yoast maps es_CO to es_ES for Open Graph; the site is Colombian Spanish.
add_filter( 'wpseo_og_locale', fn() => 'es_CO' );

// With Yoast SEO active, enrich its Organization schema with the contact data from the design.
add_filter( 'wpseo_schema_organization', function ( $data ) {
	$data['telephone'] = '+57 601 7425270';
	$data['address'] = [
		'@type' => 'PostalAddress',
		'streetAddress' => 'Carrera 103b, 23G #15',
		'addressLocality' => 'Bogotá',
		'addressRegion' => 'Bogotá D.C.',
		'addressCountry' => 'CO',
	];
	$data['contactPoint'] = [
		'@type' => 'ContactPoint',
		'telephone' => '+57 601 7425270',
		'contactType' => 'customer service',
		'areaServed' => 'CO',
		'availableLanguage' => 'es',
	];
	return $data;
} );

// Logo in the header: never lazy-load (above the fold).
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( (int) $attachment->ID === 25 ) {
		$attr['loading'] = 'eager';
		$attr['decoding'] = 'async';
		unset( $attr['fetchpriority'] );
	}
	return $attr;
}, 10, 2 );

// Fonts: Elementor would load all 18 Roboto styles; the design uses 300-700 only.
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="stylesheet" id="gf-fonts-css" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&amp;display=swap">' . "\n";
}, 5 );

// Lighter front end.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

// Small interactions (vanilla JS, no dependencies).
add_action( 'wp_footer', function () {
	?>
<script id="gf-site-js">
(function () {
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// Counters: with reduced motion show the final value right away.
	if (reduce) {
		document.querySelectorAll('.elementor-counter-number').forEach(function (el) {
			el.setAttribute('data-from-value', el.getAttribute('data-to-value'));
			el.setAttribute('data-duration', '0');
			var v = el.getAttribute('data-to-value') || '';
			var d = el.getAttribute('data-delimiter');
			el.textContent = d ? v.replace(/\B(?=(\d{3})+(?!\d))/g, d) : v;
		});
	}

	// Plans: stratum selector (1-3 / 4-6) with price crossfade.
	// The selector behaves like a switch: a pill slides to the active option (CSS .gf-seg::before).
	document.querySelectorAll('.gf-plans').forEach(function (sec) {
		var seg = sec.querySelector('.gf-seg');
		var opts = Array.prototype.slice.call(sec.querySelectorAll('.gf-seg__opt'));
		var current = '13';
		function set(v, animate) {
			if (v === current && animate) { return; }
			current = v;
			sec.classList.toggle('gf-is-46', v === '46');
			opts.forEach(function (o) {
				var on = o.getAttribute('data-estrato') === v;
				o.classList.toggle('is-active', on);
				o.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			if (seg) {
				seg.setAttribute('data-active', v);
				if (animate && !reduce) {
					seg.classList.remove('is-switching');
					void seg.offsetWidth; // restart the squish animation
					seg.classList.add('is-switching');
				}
			}
		}
		if (seg) { seg.addEventListener('animationend', function () { seg.classList.remove('is-switching'); }); }
		opts.forEach(function (o, idx) {
			o.addEventListener('click', function () { set(o.getAttribute('data-estrato'), true); });
			o.addEventListener('keydown', function (e) {
				if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') { return; }
				e.preventDefault();
				var next = opts[(idx + (e.key === 'ArrowRight' ? 1 : opts.length - 1)) % opts.length];
				next.focus();
				set(next.getAttribute('data-estrato'), true);
			});
		});
		var q = (location.search + location.hash).toLowerCase();
		set(/estrato=4-6|estratos-4-6/.test(q) ? '46' : '13', false);
	});

	// FAQ: first item open by default (as in the design). Elementor's accordion
	// handler closes everything on init, so open it once the page has loaded.
	// The handler is lazy-loaded, so retry until the click is handled.
	document.querySelectorAll('.gf-faq:not(.gf-faq--closed) .elementor-accordion-item:first-child .elementor-tab-title').forEach(function (t) {
		var tries = 0;
		(function tryOpen() {
			if (t.classList.contains('elementor-active') || tries++ > 40) { return; }
			t.click();
			setTimeout(tryOpen, 250);
		})();
	});

	// Footer columns and document index collapse on mobile.
	var mq = window.matchMedia('(max-width: 767px)');
	document.querySelectorAll('.gf-fcol, .gf-toc').forEach(function (col) {
		var head = col.querySelector('.gf-fcol__head, .gf-toc__head');
		if (!head) { return; }
		var title = head.querySelector('.elementor-heading-title');
		if (title && !title.querySelector('.gf-toggle')) {
			var s = document.createElement('span'); s.className = 'gf-toggle'; s.setAttribute('aria-hidden', 'true'); s.textContent = '+';
			title.appendChild(s);
		}
		head.setAttribute('role', 'button'); head.setAttribute('tabindex', '0');
		function sync() {
			if (mq.matches) { col.classList.add('is-collapsed'); head.setAttribute('aria-expanded', 'false'); }
			else { col.classList.remove('is-collapsed'); head.removeAttribute('aria-expanded'); }
		}
		function toggle() {
			if (!mq.matches) { return; }
			var open = col.classList.toggle('is-collapsed') === false;
			head.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		head.addEventListener('click', toggle);
		head.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
		sync();
		if (mq.addEventListener) { mq.addEventListener('change', sync); }
	});

	// ---------- Modern animations (all skipped/neutral with reduced motion) ----------
	var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

	// Reading progress bar.
	var bar = document.createElement('div');
	bar.className = 'gf-progress'; bar.setAttribute('aria-hidden', 'true');
	document.body.appendChild(bar);
	function progress() {
		var h = document.documentElement, max = h.scrollHeight - h.clientHeight;
		bar.style.setProperty('--gf-p', max > 0 ? Math.min(1, (window.scrollY || h.scrollTop) / max).toFixed(4) : 0);
	}
	// Scroll events are already dispatched once per frame; a single style write is cheap.
	window.addEventListener('scroll', progress, { passive: true });
	window.addEventListener('resize', progress, { passive: true });
	progress();

	// H1: words rise from a mask (text stays one readable string for screen readers).
	if (!reduce) {
		document.querySelectorAll('main h1.elementor-heading-title').forEach(function (h) {
			var i = 0;
			(function wrap(node) {
				Array.prototype.slice.call(node.childNodes).forEach(function (n) {
					if (n.nodeType === 3) {
						var frag = document.createDocumentFragment();
						n.textContent.split(/(\s+)/).forEach(function (part) {
							if (!part) { return; }
							if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
							var w = document.createElement('span'), inner = document.createElement('span');
							w.className = 'gf-w'; inner.className = 'gf-w__i';
							inner.style.setProperty('--i', i++); inner.textContent = part;
							w.appendChild(inner); frag.appendChild(w);
						});
						n.parentNode.replaceChild(frag, n);
					} else if (n.nodeType === 1 && n.tagName !== 'BR') { wrap(n); }
				});
			})(h);
			var widget = h.closest('.elementor-widget');
			if (widget) { widget.classList.add('gf-split-ready'); }
			h.classList.add('gf-split-run');
		});
	}

	// Cards: glow that follows the cursor.
	if (finePointer) {
		var lastEv = null, raf = 0;
		document.addEventListener('pointermove', function (e) {
			lastEv = e;
			if (raf) { return; }
			raf = requestAnimationFrame(function () {
				raf = 0;
				var c = lastEv.target && lastEv.target.closest ? lastEv.target.closest('.gf-card, .gf-dark-card') : null;
				if (!c) { return; }
				var r = c.getBoundingClientRect();
				c.style.setProperty('--mx', (lastEv.clientX - r.left) + 'px');
				c.style.setProperty('--my', (lastEv.clientY - r.top) + 'px');
			});
		}, { passive: true });
	}

	// Dark panels with the fiber network: subtle depth that follows the cursor.
	if (finePointer && !reduce) {
		document.querySelectorAll('.gf-ph--dark').forEach(function (p) {
			var net = p.querySelector('.gf-net:not(.gf-parallax-scroll)');
			if (!net) { return; }
			var pr = 0;
			p.addEventListener('pointermove', function (e) {
				if (pr) { return; }
				pr = requestAnimationFrame(function () {
					pr = 0;
					var r = p.getBoundingClientRect();
					var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
					net.style.transform = 'translate3d(' + (-x * 18).toFixed(1) + 'px,' + (-y * 14).toFixed(1) + 'px,0) scale(1.05)';
				});
			}, { passive: true });
			p.addEventListener('pointerleave', function () { net.style.transform = ''; });
		});
	}

	// Document index: highlight the section in view.
	var tocLinks = document.querySelectorAll('.gf-toc__list a[href^="#"]');
	if (tocLinks.length && 'IntersectionObserver' in window) {
		var byId = {};
		tocLinks.forEach(function (a) {
			var el = document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)));
			if (el) { byId[el.id] = a; }
		});
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting || !byId[e.target.id]) { return; }
				tocLinks.forEach(function (l) { l.classList.remove('is-active'); l.removeAttribute('aria-current'); });
				byId[e.target.id].classList.add('is-active');
				byId[e.target.id].setAttribute('aria-current', 'true');
			});
		}, { rootMargin: '-15% 0px -70% 0px' });
		Object.keys(byId).forEach(function (id) { io.observe(document.getElementById(id)); });
		tocLinks[0].classList.add('is-active');
	}
})();
</script>
	<?php
}, 5 );
