<h1>Cloudflare service manager</h1>

<h2>Installation:</h2>
<strong>Composer:</strong> <br/>
composer require andyworkbase/magecloud-cloudflare-manager <br/>
composer update <br/>

<strong>Manually:</strong> <br/>
1) unpack extension package and upload them into Magento root directory/app/code/
2) php bin/magento setup:upgrade
3) php bin/magento setup:di:compile
4) php bin/magento setup:static-content:deploy

<strong>Manager</strong> - System -> Cache Management -> Cloudflare Manager

<strong>Configuration</strong> - Stores -> Configuration -> MageCloud -> Cloudflare Manager

<h2>Features:</h2>
<ul>
<li>check Cloudflare services state;</li>
<li>purge Cloudflare service cache bu URL(s);</li>
<li>purge Cloudflare service all cache;</li>
<li>automatically purge Cloudflare service cache (purge cache when clicking the 'Flush Cache Storage' button in System -> Cache Management);</li>
</ul>

<h2>Available CLI commands:</h2>
<ul>
<li>php bin/magento magecloud:cloudflare-manager:state;</li>
<li>php bin/magento magecloud:cloudflare-manager:purge-by-url;</li>
<li>php bin/magento magecloud:cloudflare-manager:purge-all;</li>
</ul>
