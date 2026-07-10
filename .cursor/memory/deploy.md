# Deploy


## 2026-07-09 22:55 UTC

10.07 deploy prod commit 8213b24: blog JPG covers (24), blog-covers.php, in-body BYD Seal figures, blog-seo migrations, single hero, b-blog-links landings, images/blog ~6.4MB. scp + GitHub Actions OK. post-sitemap 15 URL. BYD Seal hero JPG verified.

## 2026-07-09 23:08 UTC

10.07 deploy prod commit 2204ee8: removed blog single hero cover (single.php + app.css margins). GitHub Actions OK.

## 2026-07-10 01:28 UTC

10.07 perf: nginx microcache / и /avtodoma/ (5m, bypass wp logged-in), static expires 30d. Theme: inc/performance.php trims swiper/lg/fancybox/select2 on landing pages; avto loop gallery-thumb 460px + lazy; onload.all.js guards. UTM Set-Cookie ignored in HTML cache.
## 2026-07-10 08:22 UTC

10.07 EOD: prod на HEAD c65946e (8213b24→2204ee8→88ef833→c65946e). GitHub Actions deploy theme OK. post-sitemap 17 URL verified.
