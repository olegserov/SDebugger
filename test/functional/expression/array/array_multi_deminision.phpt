$a = array('b' => "world");
$b = array('c' => array('x' => '!'));
$xxx = 'x';
----------------------
Hellow {$a.b}{$b.c.x} - {$b.c.x[$xxx]}
----------------------
Hellow world! - !