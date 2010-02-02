$array = array(
    'apple',
    'orange',
    'windows'
);
------------------
<ul>
{foreach $array as $value}
    <li>{$value}</li>
{/foreach}
</ul>
---------------
<ul>
    <li>apple</li>
    <li>orange</li>
    <li>windows</li>
</ul>