$array = array(
    'apple',
    'orange',
    array('windows', 'linux')
);
------------------
<ul>
{foreach $array as $value}
    {foreach $value as $subvalue}
        <li>{$subvalue}</li>
    {foreachelse}
        <li>{$value}</li>
    {/foreach}
{/foreach}
</ul>
---------------
<ul>
        <li>apple</li>
        <li>orange</li>
        <li>windows</li>
        <li>linux</li>
</ul>