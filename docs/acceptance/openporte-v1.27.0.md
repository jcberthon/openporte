# Acceptance criteria

## wp-env manual test steps

Tests **(a)**, **(b)** and **(c)** are our non-regression tests
(aka regression tests, but in French we say non-regression, and
as it's the goal we want, I prefer that way of saying).

**(a) Self-hosted form submits and verifies**
1. [X] wp-env start
2. [X] Activate the plugin; confirm Settings → ALTCHA shows "API Mode: Self-hosted" by default
3. [X] Enable an integration (e.g. WordPress → Comments)
4. [X] Submit a comment with the ALTCHA widget — confirm the entry is created; no PHP errors in wp-env logs
5. [X] vérifie aussi qu'il n'y a aucune requête réseau sortante vers altcha.org dans l'onglet Network du navigateur (le but de tout l'exercice : plus de dépendance externe). C'est le test qui prouve que le SaaS est vraiment parti.

**(b) Switching to "custom" mode shows the Challenge URL field**
1. [X] In Settings → ALTCHA, change "API Mode" to "Custom"
2. [X] Confirm the "Challenge URL" text input becomes enabled immediately (JS toggle)
3. [X] Enter any URL; confirm it saves; confirm the widget's challengeurl attribute reflects it in the rendered HTML
4. [X] Vérif négative : en mode Self-hosted, confirme que le champ Challenge URL est bien désactivé (c'est le pendant du toggle [data-custom-api] ; ça teste que tu n'as pas cassé le JS)

**(c) No PHP notices in wp-env logs, no error or warning in browser dev console**
1. [X] After each action above, run wp-env logs and confirm no PHP Warning, PHP Notice, or Undefined errors related to altcha_api_key, get_api_key, or the removed functions
2. [X] After each action above, check in the browser development console that there are no warning or error.

**(d) Upgrade successful**
1. [X] Teste une install héritée : avant wp-env start, mets manuellement altcha_api à "eu" en base (wp option update altcha_api eu via wp-env run cli), puis charge une page avec widget et confirme que le challenge tape bien le REST local et pas eu.altcha.org. C'est le scénario de dégradation gracieuse, le plus facile à casser. Avant de tester, force une vieille valeur en base puis vérifie la dégradation gracieuse : `wp-env run cli wp option update altcha_api eu` puis puis recharge une page avec widget → le challenge doit taper le REST local, pas `eu.altcha.org`.

**(e) PHP and Wordpress compatibility verification**
1. [-] Test with PHP 7.3 and Wordpress 5.0 - oldest combo PHP/Wordpress versions, but deprecated, support will be removed in versions after 1.27.*
2. [x] Test with PHP 8.0 and Wordpress 5.6 - minimum PHP version supported, newest WP version available
3. [x] Test with PHP 8.3 and Wordpress 6.8 - oldest maintained PHP version, the 2nd oldest version after the current WP stable version (7.0)
4. [x] Test with PHP 8.5 and Wordpress 7.0 - newest PHP version, newest WP version available

The test 1. failed. The function core.php:generate_challenge() is calling str_ends_with() standard function which was introduced in PHP 8.0 ([PHP 8.0 New Features - Official](https://www.php.net/manual/en/migration80.new-features.php#migration80.new-features.standard)). This is existing (aka legacy) code, so v1.26.3 was already requiring PHP 8.0 but the documentation (readme.txt) wasn't updated. The first Wordpress version to support PHP 8.0 is Wordpress 5.6. As part of this issue, the documentation (all where necessary) requires an update of the minimum requirements.

