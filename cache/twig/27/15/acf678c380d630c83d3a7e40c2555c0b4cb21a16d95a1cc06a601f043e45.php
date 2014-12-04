<?php

/* subscribe-buttons.twig */
class __TwigTemplate_2715acf678c380d630c83d3a7e40c2555c0b4cb21a16d95a1cc06a601f043e45 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<div class=\"ai1ec-subscribe-container ai1ec-btn-group ai1ec-pull-left
\t\tai1ec-tooltip-trigger\" data-placement=\"left\" title=\"";
        // line 2
        echo twig_escape_filter($this->env, $this->getAttribute((isset($context["text"]) ? $context["text"] : null), "tooltip"), "html", null, true);
        echo "\">
\t<button
\t\ttype=\"button\"
\t\tclass=\"ai1ec-btn ai1ec-btn-default ai1ec-btn-sm ai1ec-dropdown-toggle
\t\t\tai1ec-subscribe ";
        // line 6
        echo twig_escape_filter($this->env, (isset($context["button_classes"]) ? $context["button_classes"] : null), "html", null, true);
        echo "\"
\t\t\tdata-toggle=\"ai1ec-dropdown\">
\t\t<i class=\"ai1ec-fa ai1ec-icon-rss ai1ec-fa-lg ai1ec-fa-fw\"></i>
\t\t<span class=\"ai1ec-hidden-xs\">
\t\t\t<span class=\"ai1ec-hidden-xs\">
\t\t\t";
        // line 11
        if ((!twig_test_empty((isset($context["subscribe_label"]) ? $context["subscribe_label"] : null)))) {
            // line 12
            echo "\t\t\t\t";
            echo twig_escape_filter($this->env, (isset($context["subscribe_label"]) ? $context["subscribe_label"] : null), "html", null, true);
            echo "
\t\t\t";
        } else {
            // line 14
            echo "\t\t\t\t";
            if ((isset($context["is_filtered"]) ? $context["is_filtered"] : null)) {
                // line 15
                echo "\t\t\t\t\t";
                echo twig_escape_filter($this->env, (isset($context["text_filtered"]) ? $context["text_filtered"] : null), "html", null, true);
                echo "
\t\t\t\t";
            } else {
                // line 17
                echo "\t\t\t\t\t";
                echo twig_escape_filter($this->env, (isset($context["text_subscribe"]) ? $context["text_subscribe"] : null), "html", null, true);
                echo "
\t\t\t\t";
            }
            // line 19
            echo "\t\t\t";
        }
        // line 20
        echo "\t\t\t</span>
\t\t\t<span class=\"ai1ec-caret\"></span>
\t\t</span>
\t</button>
\t";
        // line 24
        $context["url"] = (strtr((isset($context["export_url"]) ? $context["export_url"] : null), array("webcal://" => "http://")) . (isset($context["url_args"]) ? $context["url_args"] : null));
        // line 25
        echo "\t<ul class=\"ai1ec-dropdown-menu ai1ec-pull-right\" role=\"menu\">
\t\t<li>
\t\t\t<a class=\"ai1ec-tooltip-trigger ai1ec-tooltip-auto\"
\t\t\t   target=\"_blank\"
\t\t\t   data-placement=\"left\"
\t\t\t   href=\"";
        // line 30
        echo twig_escape_filter($this->env, (isset($context["url"]) ? $context["url"] : null), "html_attr");
        echo "\"
\t\t\t   title=\"";
        // line 31
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "title"), "timely"), "html", null, true);
        echo "\" >
\t\t\t\t<i class=\"ai1ec-fa ai1ec-fa-lg ai1ec-fa-fw ai1ec-icon-timely\"></i>
\t\t\t\t";
        // line 33
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "label"), "timely"), "html", null, true);
        echo "
\t\t\t</a>
\t\t</li>
\t\t<li>
\t\t\t<a class=\"ai1ec-tooltip-trigger ai1ec-tooltip-auto\"
\t\t\t   target=\"_blank\"
\t\t\t   data-placement=\"left\"
\t\t\t   href=\"http://www.google.com/calendar/render?cid=";
        // line 40
        echo twig_escape_filter($this->env, twig_urlencode_filter((isset($context["url"]) ? $context["url"] : null)), "html_attr");
        echo "\"
\t\t\t   title=\"";
        // line 41
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "title"), "google"), "html", null, true);
        echo "\" >
\t\t\t\t<i class=\"ai1ec-fa ai1ec-icon-google ai1ec-fa-lg ai1ec-fa-fw\"></i>
\t\t\t\t";
        // line 43
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "label"), "google"), "html", null, true);
        echo "
\t\t\t</a>
\t\t</li>
\t\t<li>
\t\t\t<a class=\"ai1ec-tooltip-trigger ai1ec-tooltip-auto\"
\t\t\t   target=\"_blank\"
\t\t\t   data-placement=\"left\"
\t\t\t   href=\"";
        // line 50
        echo twig_escape_filter($this->env, ((isset($context["export_url_no_html"]) ? $context["export_url_no_html"] : null) . (isset($context["url_args"]) ? $context["url_args"] : null)), "html_attr");
        echo "\"
\t\t\t   title=\"";
        // line 51
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "title"), "outlook"), "html", null, true);
        echo "\" >
\t\t\t\t<i class=\"ai1ec-fa ai1ec-icon-windows ai1ec-fa-lg ai1ec-fa-fw\"></i>
\t\t\t\t";
        // line 53
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "label"), "outlook"), "html", null, true);
        echo "
\t\t\t</a>
\t\t</li>
\t\t<li>
\t\t\t<a class=\"ai1ec-tooltip-trigger ai1ec-tooltip-auto\"
\t\t\t   target=\"_blank\"
\t\t\t   data-placement=\"left\"
\t\t\t   href=\"";
        // line 60
        echo twig_escape_filter($this->env, ((isset($context["export_url_no_html"]) ? $context["export_url_no_html"] : null) . (isset($context["url_args"]) ? $context["url_args"] : null)), "html_attr");
        echo "\"
\t\t\t   title=\"";
        // line 61
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "title"), "apple"), "html", null, true);
        echo "\" >
\t\t\t\t<i class=\"ai1ec-fa ai1ec-icon-apple ai1ec-fa-lg ai1ec-fa-fw\"></i>
\t\t\t\t";
        // line 63
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "label"), "apple"), "html", null, true);
        echo "
\t\t\t</a>
\t\t</li>
\t\t<li>
\t\t\t";
        // line 67
        $context["export_url_no_html_http"] = strtr((isset($context["export_url_no_html"]) ? $context["export_url_no_html"] : null), array("webcal://" => "http://"));
        // line 68
        echo "\t\t\t<a class=\"ai1ec-tooltip-trigger ai1ec-tooltip-auto\"
\t\t\t   data-placement=\"left\"
\t\t\t   href=\"";
        // line 70
        echo twig_escape_filter($this->env, ((isset($context["export_url_no_html_http"]) ? $context["export_url_no_html_http"] : null) . (isset($context["url_args"]) ? $context["url_args"] : null)), "html_attr");
        echo "\"
\t\t\t   title=\"";
        // line 71
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "title"), "plaintext"), "html", null, true);
        echo "\" >
\t\t\t\t<i class=\"ai1ec-fa ai1ec-icon-calendar ai1ec-fa-fw\"></i>
\t\t\t\t";
        // line 73
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context["text"]) ? $context["text"] : null), "label"), "plaintext"), "html", null, true);
        echo "
\t\t\t</a>
\t\t</li>
\t</ul>
</div>
";
    }

    public function getTemplateName()
    {
        return "subscribe-buttons.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  153 => 68,  120 => 51,  758 => 246,  751 => 241,  749 => 240,  732 => 237,  730 => 236,  722 => 234,  716 => 232,  714 => 231,  708 => 228,  704 => 227,  700 => 226,  693 => 222,  689 => 220,  683 => 217,  679 => 216,  676 => 215,  674 => 214,  670 => 212,  665 => 210,  661 => 209,  657 => 207,  654 => 206,  649 => 204,  646 => 203,  640 => 201,  632 => 199,  622 => 195,  620 => 194,  616 => 193,  604 => 186,  601 => 185,  599 => 184,  589 => 180,  584 => 177,  577 => 174,  571 => 171,  560 => 167,  554 => 166,  548 => 165,  542 => 164,  536 => 163,  532 => 162,  527 => 161,  524 => 160,  521 => 159,  518 => 158,  515 => 157,  513 => 156,  509 => 155,  505 => 154,  501 => 153,  490 => 151,  485 => 150,  481 => 149,  477 => 148,  473 => 147,  468 => 145,  464 => 144,  459 => 143,  456 => 142,  452 => 141,  447 => 138,  443 => 136,  434 => 132,  428 => 130,  425 => 129,  419 => 128,  408 => 125,  404 => 124,  398 => 121,  393 => 119,  387 => 118,  384 => 117,  380 => 116,  377 => 115,  375 => 114,  367 => 112,  350 => 111,  346 => 110,  340 => 106,  338 => 105,  322 => 103,  320 => 102,  306 => 98,  294 => 93,  283 => 88,  279 => 86,  266 => 81,  264 => 80,  244 => 72,  206 => 59,  200 => 55,  168 => 43,  336 => 116,  334 => 115,  327 => 114,  325 => 113,  318 => 112,  316 => 111,  298 => 94,  292 => 103,  284 => 99,  276 => 97,  255 => 76,  246 => 83,  198 => 66,  178 => 57,  176 => 56,  148 => 46,  112 => 35,  102 => 31,  85 => 24,  58 => 15,  136 => 46,  133 => 45,  110 => 36,  98 => 30,  100 => 31,  90 => 27,  39 => 12,  311 => 116,  290 => 92,  278 => 106,  272 => 103,  267 => 101,  243 => 89,  240 => 88,  235 => 86,  209 => 76,  204 => 68,  202 => 72,  193 => 67,  187 => 61,  166 => 73,  144 => 63,  141 => 45,  92 => 29,  319 => 94,  317 => 93,  313 => 92,  310 => 109,  307 => 115,  305 => 107,  302 => 82,  291 => 77,  287 => 75,  285 => 74,  280 => 98,  277 => 71,  271 => 69,  269 => 82,  253 => 94,  238 => 65,  218 => 63,  189 => 46,  180 => 42,  163 => 36,  45 => 14,  42 => 11,  50 => 8,  47 => 7,  226 => 66,  220 => 73,  188 => 69,  182 => 66,  175 => 60,  152 => 47,  143 => 36,  122 => 38,  97 => 40,  93 => 25,  86 => 23,  71 => 25,  293 => 100,  289 => 99,  260 => 78,  254 => 88,  251 => 75,  248 => 92,  239 => 70,  237 => 80,  233 => 78,  230 => 67,  225 => 75,  222 => 65,  210 => 60,  208 => 70,  195 => 53,  171 => 54,  161 => 71,  159 => 40,  154 => 47,  150 => 38,  146 => 37,  137 => 34,  132 => 40,  126 => 36,  121 => 37,  118 => 33,  116 => 50,  111 => 35,  105 => 27,  99 => 27,  95 => 26,  83 => 23,  78 => 30,  62 => 17,  59 => 11,  38 => 9,  33 => 7,  29 => 6,  25 => 4,  164 => 42,  156 => 48,  139 => 61,  131 => 42,  127 => 42,  123 => 38,  114 => 38,  104 => 27,  96 => 31,  77 => 21,  74 => 19,  60 => 19,  69 => 24,  644 => 202,  636 => 200,  628 => 197,  619 => 336,  617 => 335,  611 => 331,  609 => 188,  606 => 329,  600 => 326,  597 => 325,  595 => 183,  592 => 323,  585 => 319,  580 => 175,  576 => 316,  573 => 172,  570 => 314,  567 => 169,  558 => 306,  549 => 300,  543 => 297,  534 => 291,  525 => 285,  520 => 282,  517 => 281,  510 => 275,  504 => 273,  497 => 152,  495 => 269,  489 => 265,  483 => 263,  476 => 260,  474 => 259,  469 => 256,  461 => 250,  453 => 245,  439 => 234,  433 => 230,  426 => 224,  420 => 222,  413 => 219,  411 => 126,  405 => 214,  399 => 212,  392 => 209,  390 => 208,  385 => 205,  379 => 200,  373 => 198,  366 => 195,  364 => 194,  359 => 191,  354 => 187,  348 => 184,  345 => 183,  339 => 180,  333 => 177,  330 => 176,  328 => 175,  324 => 173,  321 => 171,  315 => 118,  312 => 100,  304 => 97,  301 => 159,  297 => 112,  295 => 78,  288 => 110,  282 => 73,  275 => 70,  273 => 83,  268 => 142,  262 => 98,  256 => 135,  249 => 132,  242 => 83,  236 => 69,  216 => 72,  212 => 61,  207 => 117,  203 => 52,  192 => 52,  183 => 63,  177 => 61,  170 => 38,  155 => 39,  145 => 71,  138 => 47,  134 => 32,  119 => 40,  107 => 29,  101 => 41,  91 => 23,  80 => 22,  66 => 14,  35 => 6,  30 => 6,  63 => 20,  54 => 17,  43 => 7,  24 => 3,  21 => 2,  82 => 31,  73 => 20,  70 => 19,  64 => 20,  55 => 14,  52 => 15,  48 => 15,  46 => 10,  41 => 11,  37 => 11,  32 => 10,  22 => 2,  88 => 25,  81 => 25,  79 => 24,  75 => 22,  68 => 18,  57 => 14,  49 => 12,  44 => 7,  31 => 5,  27 => 6,  265 => 91,  259 => 88,  252 => 86,  250 => 85,  247 => 73,  241 => 81,  234 => 68,  232 => 108,  229 => 83,  227 => 77,  219 => 100,  213 => 77,  205 => 53,  201 => 91,  199 => 51,  196 => 65,  190 => 51,  186 => 50,  184 => 83,  181 => 48,  173 => 45,  169 => 77,  167 => 52,  162 => 89,  160 => 53,  157 => 70,  151 => 67,  149 => 47,  142 => 44,  135 => 60,  130 => 40,  128 => 31,  125 => 53,  117 => 36,  113 => 58,  108 => 29,  106 => 43,  103 => 28,  94 => 29,  89 => 26,  87 => 33,  84 => 25,  76 => 28,  72 => 21,  67 => 19,  65 => 15,  61 => 16,  56 => 17,  53 => 17,  51 => 13,  40 => 6,  34 => 8,  28 => 5,  26 => 3,  36 => 9,  23 => 3,  19 => 1,);
    }
}
