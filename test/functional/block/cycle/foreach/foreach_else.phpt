$array = array(
    'apple',
    'orange',
    array('windows', 'linux')
);
------------------
<ul>
{foreach $array as $key => $value}
    {foreach $value as $subvalue}
        <li>{$key}: {$subvalue}</li>
    {foreachelse}
        <li>{$key}: {$value}</li>
    {/foreach}
{/foreach}
</ul>
---------------
<ul>
        <li>0: apple</li>
        <li>1: orange</li>
        <li>2: windows</li>
        <li>2: linux</li>
</ul>