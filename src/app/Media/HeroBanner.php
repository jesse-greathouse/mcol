<?php

namespace App\Media;

/**
 * Class HeroBanner
 *
 * Generates an SVG representation of a hero banner, including:
 * - Link to donate.
 */
class HeroBanner
{
    const STRIPE_DONATION_LINK = 'https://donate.stripe.com/00g9Co2YigAXdzO4gg';

    /**
     * Attributes that define various SVG element positions, dimensions, and defaults.
     * Some attributes may be overridden in the constructor.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [
        'width' => 1200,
        'height' => 400,
        'link' => self::STRIPE_DONATION_LINK,
    ];

    /**
     * User-provided `$attributes` override default values.
     *
     * @param  ?array  $attributes  Optional key-value pairs to override default attributes.
     */
    public function __construct(?array $attributes = [])
    {
        //  Override class defaults with user-provided attributes
        foreach ($attributes as $key => $val) {
            $this->attributes[$key] = $val;
        }
    }

    /**
     * Generates an SVG
     *
     * This method constructs an SVG Hero Image
     *
     * @return string SVG markup
     */
    public function toSvg(): string
    {
        // Extract attributes for cleaner references
        [
            'height' => $height,
            'width' => $width,
            'link' => $link,
        ] = $this->attributes;

        // Start SVG output
        $svg = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $svg .= "<svg xmlns='http://www.w3.org/2000/svg' width='100%' viewBox='0 0 {$width} {$height}' preserveAspectRatio='xMidYMid meet' style='aspect-ratio: {$width} / {$height};'>\n";

        // Contains the style information for the card design
        $svg .= $this->addStyleElement();

        // Background
        $svg .= "<rect width='100%' height='100%' class='bg-gray' />\n";

        // Octopus graphic
        $svg .= "<g transform='translate(40, 40) scale(0.6)'>\n";
        $svg .= $this->addOctopusSvg();
        $svg .= "</g>\n";

        // Headline text
        $svg .= "<text x='500' y='120' text-anchor='start' class='txt headline'>Independant and open source</text>\n";

        // Subheadline
        $svg .= "<text x='500' y='180' text-anchor='start' class='txt subheadline'>Donate the price of a coffee to keep this project alive.</text>\n";

        // Donation button link (simulated)
        $svg .= "<a xlink:href='{$link}' target='_blank'>\n";
        $svg .= "  <rect x='500' y='250' rx='8' ry='8' width='300' height='60' class='btn-bg' />\n";
        $svg .= "  <text x='650' y='290' text-anchor='middle' class='txt btn-text'>☕ Donate Now</text>\n";
        $svg .= "</a>\n";

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Appends a style element to the svg document.
     *
     * @return string SVG `<style>` element.
     */
    public static function addStyleElement(): string
    {
        return <<<EOD
        <style type="text/css">
            .txt { font-family: Arial, Helvetica, sans-serif; font-style: normal; }
            .headline { font-size: 48px; fill: #111827; }
            .subheadline { font-size: 24px; fill: #374151; }
            .btn-bg { fill: #2563eb; }
            .btn-text { font-size: 20px; fill: white; }
            .bg-gray { fill: #f3f4f6; }
        </style>\n
        EOD;
    }

    /**
     * Returns inline SVG markup for the octopus graphic.
     */
    protected function addOctopusSvg(): string
    {
        return <<<'SVG'
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><g><g><path d="M377.437,463.923c44.562,2.453,73.459-22.544,79.252-28.351l-11.594-25.766 c-5.8,5.793-32.106,25.674-67.658,19.328c-34.96-6.241-49.954-32.527-56.05-49.875c12.732,12.35,37.67,26.444,77.312,9.924 c61.857-25.773,54.13-82.481,54.13-100.513l-21.913-1.296c0,36.834-18.04,64.436-52.835,65.724 c-14.179-1.289-36.078-28.351-21.255-42.911c32.882-32.882,53.223-78.305,53.223-128.483s-20.341-95.601-53.223-128.483 C323.945,20.341,278.523,0,228.345,0c-50.171,0-95.608,20.341-128.479,53.222c-32.889,32.882-53.222,78.305-53.222,128.483 s20.334,95.601,53.222,128.483c14.823,14.56-7.083,41.622-21.259,42.911c-34.789-1.289-52.831-28.89-52.831-65.724L3.867,288.67 c0,18.032-7.734,74.74,54.124,100.513c39.642,16.52,64.584,2.426,77.319-9.924c-6.097,17.349-21.091,43.634-56.057,49.875 c-35.546,6.346-61.854-13.534-67.651-19.328L0,435.572c5.797,5.807,34.694,30.804,79.252,28.351 c70.235-3.867,98.586-56.701,98.586-56.701c2.545,17.348,1.46,37.275-25.069,59.115c-27.877,22.958-58.02,18.341-65.924,16.125 l1.345,25.839c7.892,2.21,53.598,10.417,84.48-8.286c51.296-31.041,55.676-87.637,55.676-87.637s4.38,56.596,55.676,87.637 c30.882,18.703,76.588,10.496,84.487,8.286l1.335-25.839c-7.898,2.216-38.044,6.833-65.921-16.125 c-26.529-21.84-27.614-41.767-25.069-59.115C278.851,407.222,307.202,460.057,377.437,463.923z" style="fill: rgb(125, 164, 227);"></path> <path d="M154.768,172.037c0,8.971-7.267,16.244-16.237,16.244c-8.964,0-16.234-7.274-16.234-16.244 c0-8.963,7.27-16.237,16.234-16.237C147.501,155.801,154.768,163.074,154.768,172.037z" style="fill: rgb(114, 113, 113);"></path> <path d="M328.746,172.037c0,8.971-7.274,16.244-16.238,16.244c-8.97,0-16.243-7.274-16.243-16.244 c0-8.963,7.274-16.237,16.243-16.237C321.473,155.801,328.746,163.074,328.746,172.037z" style="fill: rgb(114, 113, 113);"></path> <path d="M324.103,270.624c0,20.288-16.441,36.729-36.729,36.729H186.861 c-20.288,0-36.735-16.441-36.735-36.729l0,0c0-20.288,16.447-36.729,36.735-36.729h100.513 C307.662,233.895,324.103,250.336,324.103,270.624L324.103,270.624z" style="fill: rgb(85, 102, 170);"></path> <path d="M318.303,262.891c0,20.288-16.441,36.729-36.729,36.729h-100.52 c-20.281,0-36.722-16.441-36.722-36.729l0,0c0-20.288,16.441-36.723,36.722-36.723h100.52 C301.862,226.168,318.303,242.602,318.303,262.891L318.303,262.891z" style="fill: rgb(212, 227, 247);"></path> <g><path d="M186.861,272.557h86.986c5.333,0,9.661-4.327,9.661-9.667c0-5.333-4.327-9.661-9.661-9.661 h-86.986c-5.346,0-9.667,4.327-9.667,9.661C177.194,268.23,181.514,272.557,186.861,272.557" style="fill: rgb(114, 113, 113);"></path></g> <path d="M445.095,409.807c-5.8,5.793-32.106,25.674-67.658,19.328 c-34.96-6.241-49.954-32.527-56.05-49.875c12.732,12.35,37.67,26.444,77.312,9.924c61.857-25.773,54.13-82.481,54.13-100.513 l-21.913-1.296c0,36.834-18.04,64.436-52.835,65.724c-14.179-1.289-36.078-28.351-21.255-42.911 c32.882-32.882,53.223-78.305,53.223-128.483s-20.341-95.601-53.223-128.483C323.945,20.341,278.523,0,228.345,0v412.378 c0,0,4.38,56.596,55.676,87.637c30.882,18.703,76.588,10.496,84.487,8.286l1.335-25.839c-7.898,2.216-38.044,6.833-65.921-16.125 c-26.529-21.84-27.614-41.767-25.069-59.115c0,0,28.351,52.835,98.586,56.701c44.562,2.453,73.459-22.544,79.252-28.351 L445.095,409.807z" style="opacity: 0.06; fill: rgb(35, 24, 21);"></path></g> <path d="M158.287,85.572c0,15.178-12.311,27.489-27.49,27.489c-15.181,0-27.489-12.311-27.489-27.489 c0-15.178,12.308-27.489,27.489-27.489C145.976,58.083,158.287,70.393,158.287,85.572z" style="fill: rgb(255, 255, 255);"></path></g></g>
        SVG;
    }

    /**
     * Returns the dictionary of attributes described in creating this object.
     *
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        return $this->attributes;
    }
}
