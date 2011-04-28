<?php
/**
 * MIME Viewer configuration file.
 *
 * Default settings used for all Horde applications that require MIME
 * viewing. Individual Horde applications can override these settings
 * in their config/mime_drivers.php files.
 *
 * IMPORTANT: Local overrides MUST be placed in mime_drivers.local.php, or
 * mime_drivers-servername.php if the 'vhosts' setting has been enabled in
 * Horde's configuration.
 *
 * The 'handles' setting below shouldn't be changed in most
 * circumstances. It registers a set of MIME type that the driver can
 * handle. The 'x-extension' MIME type is a special one to Horde that
 * maps a file extension to a MIME type. It's useful when you know
 * that all files ending in '.c' are C files, for example. You can set
 * the MIME subtype to '*' to match all possible subtypes
 * (i.e. 'image/*').
 *
 * The 'icons' entry is for the driver to register various icons for
 * the MIME types it handles. The array consists of a 'default' icon
 * for that driver, and can also include specific MIME-types which can
 * have their own icons. You can set the MIME subtype to '*' to match
 * all possible subtypes (i.e. 'image/*').
 */

$mime_drivers = array(
    /* Default driver. */
    'default' => array(
        'icons' => array(
            'default'                       => 'text.png',
            'message/*'                     => 'mail.png',
            'unknown/*'                     => 'binary.png',
            'video/*'                       => 'video.png',
            'application/pgp-signature'     => 'encryption.png',
            'application/x-pkcs7-signature' => 'encryption.png',
            'application/octet-stream'      => 'binary.png'
        )
    ),

    /* Default text driver. */
    'simple' => array(
        'handles' => array(
            'text/*'
        ),
        'icons' => array(
            'default' => 'text.png'
        )
    ),

    /* Plain text driver. */
    'plain' => array(
        'inline' => true,
        'handles' => array(
            'text/plain'
        ),
        'icons' => array(
            'default' => 'text.png'
        )
    ),

    /* Default audio driver. */
    'audio' => array(
        'handles' => array(
            'audio/*'
        ),
        'icons' => array(
            'default' => 'audio.png'
        )
    ),

    /* Default smil driver. */
    'smil' => array(
        'inline' => true,
        'handles' => array(
            'application/smil'
        ),
        'icons' => array(
            'default' => 'video.png'
        )
    ),

    /* HTML display. */
    'html' => array(
        // NOTE: Inline HTML viewing is DISABLED by default.
        'inline' => false,
        'handles' => array(
            'text/html'
        ),
        'icons' => array(
            'default' => 'html.png'
        ),

        // Check for phishing exploits?
        'phishing_check' => true
    ),

    /* Enriched text display. */
    'enriched' => array(
        'inline' => true,
        'handles' => array(
            'text/enriched'
        ),
        'icons' => array(
            'default' => 'text.png'
        )
    ),

    /* Richtext display. */
    'richtext' => array(
        'inline' => true,
        'handles' => array(
            'text/richtext'
        ),
        'icons' => array(
            'default' => 'text.png'
        )
    ),

    /* SyntaxHighlighter driver.
     * http://alexgorbatchev.com/SyntaxHighlighter/ */
    'syntaxhighlighter' => array(
        'inline' => true,
        'handles' => array(
            'application/javascript',
            'application/x-extension-bat',
            'application/x-extension-c',
            'application/x-extension-cpp',
            'application/x-extension-cs',
            'application/x-extension-css',
            'application/x-extension-html',
            'application/x-extension-js',
            'application/x-extension-perl',
            'application/x-extension-php',
            'application/x-extension-php3s',
            'application/x-extension-phps',
            'application/x-extension-pl',
            'application/x-extension-py',
            'application/x-extension-python',
            'application/x-extension-rb',
            'application/x-extension-ruby',
            'application/x-extension-sh',
            'application/x-extension-vb',
            'application/x-extension-vba',
            'application/x-extension-xml',
            'application/x-httpd-php',
            'application/x-httpd-php3',
            'application/x-httpd-phps',
            'application/x-javascript',
            'application/x-perl',
            'application/x-php',
            'application/x-python',
            'application/x-ruby',
            'application/x-sh',
            'application/x-shellscript',
            'application/x-tcl',
            'application/xml',
            'text/cpp',
            'text/css',
            'text/diff',
            'text/x-c',
            'text/x-c++',
            'text/x-c++hdr',
            'text/x-c++src',
            'text/x-chdr',
            'text/x-csrc',
            'text/x-diff',
            'text/x-java',
            'text/x-patch',
            'text/x-sql',
            'text/x-tex',
            'text/xml',
        ),
        'icons' => array(
            'default'                        => 'text.png',
            'application/javascript'         => 'script-js.png',
            'application/x-extension-c'      => 'source-c.png',
            'application/x-extension-cpp'    => 'source-c.png',
            'application/x-extension-cs'     => 'source-c.png',
            'application/x-extension-css'    => 'html.png',
            'application/x-extension-html'   => 'html.png',
            'application/x-extension-js'     => 'script-js.png',
            'application/x-extension-php'    => 'php.png',
            'application/x-extension-php3s'  => 'php.png',
            'application/x-extension-phps'   => 'php.png',
            'application/x-extension-py'     => 'source-python.png',
            'application/x-extension-python' => 'source-python.png',
            'application/x-extension-xml'    => 'xml.png',
            'application/x-httpd-php'        => 'php.png',
            'application/x-httpd-php3'       => 'php.png',
            'application/x-httpd-phps'       => 'php.png',
            'application/x-javascript'       => 'script-js.png',
            'application/x-php'              => 'php.png',
            'application/x-python'           => 'source-python.png',
            'application/x-sh'               => 'shell.png',
            'application/x-shellscript'      => 'shell.png',
            'application/xml'                => 'xml.png',
            'text/cpp'                       => 'source-c.png',
            'text/css'                       => 'html.png',
            'text/x-c'                       => 'source-c.png',
            'text/x-c++'                     => 'source-c.png',
            'text/x-c++hdr'                  => 'source-c.png',
            'text/x-c++src'                  => 'source-c.png',
            'text/x-chdr'                    => 'source-h.png',
            'text/x-csrc'                    => 'source-c.png',
            'text/x-java'                    => 'source-java.png',
            'text/xml'                       => 'xml.png',
        ),
    ),

    /* Tar file display.
     * To access gzipped files, the zlib library must have been built into PHP
     * (with the --with-zlib option). */
    'tgz' => array(
        'inline' => true,
        'handles' => array(
            'application/gzip',
            'application/x-compressed-tar',
            'application/x-gtar',
            'application/x-gzip',
            'application/x-gzip-compressed',
            'application/x-tar',
            'application/x-tgz'
        ),
        'icons' => array(
            'default' => 'compressed.png'
        )
    ),

    /* Zip file display. */
    'zip' => array(
        'inline' => true,
        'handles' => array(
            'application/x-compressed',
            'application/x-zip-compressed',
            'application/zip'
        ),
        'icons' => array(
            'default' => 'compressed.png'
        )
    ),

    /* RAR archive display. */
    'rar' => array(
        'inline' => true,
        'handles' => array(
            'application/x-rar',
            'application/x-rar-compressed'
        ),
        'icons' => array(
            'default' => 'compressed.png'
        )
    ),

    /* MS Word display.
     * This driver requires AbiWord to be installed.
     * AbiWord homepage: http://www.abisource.com/ */
    'msword' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/msword',
            'application/vnd.ms-word'
        ),
        'icons' => array(
            'default' => 'msword.png'
        ),

        // REQUIRED: Location of the AbiWord binary
        'location' => '/usr/bin/abiword'
    ),

    /* MS Excel display.
     * This driver requires Gnumeric to be installed.
     * Gnumeric homepage: http://projects.gnome.org/gnumeric/ */
    'msexcel' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/msexcel',
            'application/x-msexcel',
            'application/vnd.ms-excel'
        ),
        'icons' => array(
            'default' => 'msexcel.png'
        ),

        // REQUIRED: Location of the ssconvert binary
        'location' => '/usr/bin/ssconvert'
    ),

    /* MS Powerpoint display.
     * This driver requires ppthtml, included with xlhtml, to be installed.
     * xlhtml homepage: http://chicago.sourceforge.net/xlhtml/ */
    'mspowerpoint' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/mspowerpoint',
            'application/vnd.ms-powerpoint'
        ),
        'icons' => array(
            'default' => 'mspowerpoint.png'
        ),

        // REQUIRED: Location of the ppthtml binary
        'location' => '/usr/bin/ppthtml'
    ),

    /* vCard display. */
    'vcard' => array(
        'inline' => true,
        'handles' => array(
            'text/directory',
            'text/vcard',
            'text/x-vcard'
        ),
        'icons' => array(
            'default' => 'vcard.png'
        )
    ),

    /* RPM archive display. */
    'rpm' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/x-rpm'
        ),
        'icons' => array(
            'default' => 'rpm.png'
        ),

        // REQUIRED: Location of the rpm binary
        'location' => '/usr/bin/rpm'
    ),

    /* Debian archive display. */
    'deb' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/x-deb',
            'application/x-debian-package'
        ),
        'icons' => array(
            'default' => 'deb.png'
        ),

        // REQUIRED: Location of the dpkg binary
        'location' => '/usr/bin/dpkg'
    ),

    /* Secure multipart (RFC 1847) display. */
    'security' => array(
        'inline' => true,
        'handles' => array(
            'multipart/encrypted',
            'multipart/signed'
        ),
        'icons' => array(
            'default' => 'encryption.png'
        )
    ),

    /* Image display. */
    'images' => array(
        'handles' => array(
            'image/*'
        ),
        'icons' => array(
            'default' => 'image.png'
        )
    ),

    /* MS-TNEF Attachment display. */
    'tnef' => array(
        'handles' => array(
            'application/ms-tnef'
        ),
        'icons' => array(
            'default' => 'binary.png'
        )
    ),

    /* Digest message (RFC 2046 [5.2.1]) display. */
    'rfc822' => array(
        'handles' => array(
            'message/rfc822',
            'x-extension/eml'
        ),
        'icons' => array(
            'default' => 'mail.png'
        )
    ),

    /* Mail report messages (RFC 3452) display. */
    'report' => array(
        'inline' => true,
        'handles' => array(
            'multipart/report'
        ),
        'icons' => array(
            'default' => 'mail.png'
        )
    ),

    /* OpenOffice.org/StarOffice document display. */
    'ooo' => array(
        'handles' => array(
            'application/vnd.stardivision.calc',
            'application/vnd.stardivision.draw',
            'application/vnd.stardivision.impress',
            'application/vnd.stardivision.math',
            'application/vnd.stardivision.writer',
            'application/vnd.sun.xml.calc',
            'application/vnd.sun.xml.calc.template',
            'application/vnd.sun.xml.draw',
            'application/vnd.sun.xml.draw.template',
            'application/vnd.sun.xml.impress',
            'application/vnd.sun.xml.impress.template',
            'application/vnd.sun.xml.math',
            'application/vnd.sun.xml.writer',
            'application/vnd.sun.xml.writer.global',
            'application/vnd.sun.xml.writer.template',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.text'
            ),
        'icons' => array(
            'default'                                  => 'ooo_calc.png',
            'application/vnd.stardivision.calc'        => 'ooo_calc.png',
            'application/vnd.stardivision.draw'        => 'ooo_draw.png',
            'application/vnd.stardivision.impress'     => 'ooo_impress.png',
            'application/vnd.stardivision.math'        => 'ooo_math.png',
            'application/vnd.stardivision.writer'      => 'ooo_writer.png',
            'application/vnd.sun.xml.calc'             => 'ooo_calc.png',
            'application/vnd.sun.xml.calc.template'    => 'ooo_calc.png',
            'application/vnd.sun.xml.draw'             => 'ooo_draw.png',
            'application/vnd.sun.xml.draw.template'    => 'ooo_draw.png',
            'application/vnd.sun.xml.impress'          => 'ooo_impress.png',
            'application/vnd.sun.xml.impress.template' => 'ooo_impress.png',
            'application/vnd.sun.xml.math'             => 'ooo_math.png',
            'application/vnd.sun.xml.writer'           => 'ooo_writer.png',
            'application/vnd.sun.xml.writer.global'    => 'ooo_writer.png',
            'application/vnd.sun.xml.writer.template'  => 'ooo_writer.png',
            'application/vnd.oasis.opendocument.presentation' => 'ooo_impress.png',
            'application/vnd.oasis.opendocument.spreadsheet'  => 'ooo_calc.png',
            'application/vnd.oasis.opendocument.text'         => 'ooo_writer.png'
        )
    ),

    /* Portable Document Format (PDF) display. */
    'pdf' => array(
        'handles' => array(
            'application/pdf',
            'application/x-pdf',
            'image/pdf'
        ),
        'icons' => array(
            'default' => 'pdf.png'
        )
    ),

    /* RTF display.
     * This driver requires UnRTF to be installed.
     * UnRTF homepage: http://www.gnu.org/software/unrtf/unrtf.html */
    'rtf' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/rtf',
            'text/rtf'
        ),
        'icons' => array(
            'default' => 'text.png'
        ),

        // REQUIRED: location of the unrtf binary
        'location' => '/usr/bin/unrtf'
    ),

    /* WordPerfect document display.
     * This driver requires wpd2html to be installed.
     * libwpd homepage: http://libwpd.sourceforge.net/ */
    'wordperfect' => array(
        // Disabled by default
        'disable' => true,
        'handles' => array(
            'application/vnd.wordperfect',
            'application/wordperf',
            'application/wordperfect',
            'application/wpd',
            'application/x-wpwin'
        ),
        'icons' => array(
            'default' => 'wordperfect.png'
        ),

        // REQUIRED: location of the wpd2html binary
        'location' => '/usr/bin/wpd2html'
    )
);
