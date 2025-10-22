<!DOCTYPE html>
<html <?php language_attributes() ?>>

<head>
    <?php wp_head() ?>
</head>

<body <?php body_class() ?>>
    <?php get_header() ?>
    <main id="main" class="main" role="main">
        <section class="container">
            <div class="page__inner">
                <?php add_breadcrumbs_auto() ?>
                <div class="page__inner__wrapper padding-block-xxxl">
                    <h1 class="project__title" style="text-align: center">
                        Error 404: Page not found
                    </h1>
                </div>
            </div>
        </section>
    </main>

    <?php
    // get_sidebar();
    get_footer();
    wp_footer();
    ?>
</body>

</html>