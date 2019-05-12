Panels
======

This directory holds Admin, Donate, Records and Vote panel templates,
managed by plugin.panels.php.
Templates define the complete ManiaLink panel with position, size and
fonts, so you have full control to develop custom panels.

To create a new template, copy an existing one to a new filename and
edit that, as the existing set will be overwritten in future releases.

Use ManiaLink http://smurf1.free.fr/mle/index.xml
and webpage   http://smurf1.free.fr/mle/list.php
to select styles and fonts.  Note that not every (sub)style and font
fits everywhere due to size variations.

New Admin templates must stick to manialink id="3" and preserve
action="21" through action="27" for the buttons.

New Donate templates must stick to manialink id="6" and preserve
action="30" through action="36" for the buttons.

New Record templates must stick to manialink id="4" and preserve the
mapping of text="%PB%" to action="7", "%LCL%" to "8", "%DED%" to "9"
and "%TMX%" to "10".

New Vote templates must stick to manialink id="5" and preserve
action="18" for the Yes button and action="19" for the No button.

If you create a nice template for any panel that's sufficiently distinct
from the standard ones, send it to me and I might include it in the
next XAseco release. :)

Xymph
