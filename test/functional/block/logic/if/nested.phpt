$a = 11;
$b = 22;
$c = 33;
---------------
{if $a < $b}
    {if $b < $c}
        $a less than $b!
    {/if}
    After!
{/if}

---------------
$a less than $b!
    After!