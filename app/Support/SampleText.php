<?php

namespace App\Support;

/**
 * Realistic English placeholder text for factories and seeders.
 * (Faker's sentence()/paragraph() produce Latin "lorem ipsum" text.)
 */
final class SampleText
{
    private const SUBJECTS = [
        'Structural Steel Connections', 'High-Voltage Transmission Lines', 'Pressure Vessel Inspection',
        'Offshore Wind Foundations', 'Industrial Control System Security', 'Wastewater Treatment Plants',
        'Reinforced Concrete Bridges', 'Lithium-Ion Battery Storage', 'Medical Device Software',
        'Corrosion Protection of Pipelines', 'Fire Safety in Tall Buildings', 'Robotic Welding Cells',
        'Seismic Retrofitting of Hospitals', 'Hydrogen Fuel Systems', 'Semiconductor Cleanroom Design',
        'Railway Signalling Systems', 'Aircraft Composite Repairs', 'Smart Grid Communications',
        'Marine Propulsion Systems', 'Non-Destructive Testing of Welds', 'Solar Photovoltaic Installations',
        'Building Energy Performance', 'Autonomous Vehicle Safety', 'Drinking Water Distribution',
    ];

    private const FORMATS = [
        'Guidelines for %s', 'Recommended Practice for %s', 'A Compliance Framework for %s',
        'Design Requirements for %s', 'Risk Assessment Methods for %s', 'Field Performance Review of %s',
        'Testing Procedures for %s', 'Best Practices in %s', 'Whitepaper: Trends in %s',
    ];

    private const ABSTRACT_SENTENCES = [
        'This paper presents a consolidated set of requirements and recommended practices drawn from recent industry experience.',
        'The proposed method was validated against field data collected from more than forty operating installations.',
        'Results indicate a measurable improvement in reliability and a reduction in lifecycle maintenance cost.',
        'The authors compare existing national and international standards and identify areas where they conflict.',
        'A step-by-step compliance checklist is provided to help engineers apply the guidance in practice.',
        'Safety factors and acceptance criteria are discussed in the context of current regulatory expectations.',
        'Case studies from three regions illustrate how the approach adapts to different environmental conditions.',
        'The findings support a revision of the current testing interval and documentation requirements.',
        'Limitations of the study are discussed, together with recommendations for further research.',
        'An appendix lists the measurement procedures and data sets so that the results can be reproduced.',
    ];

    private const REVIEW_SENTENCES = [
        'The methodology is sound and clearly described, and the conclusions follow from the evidence presented.',
        'Please clarify how the sample of installations was selected, as this affects how general the results are.',
        'The comparison with existing standards is useful, but several references should be updated to current editions.',
        'Section 3 would benefit from a worked example showing how the acceptance criteria are applied.',
        'The safety factors in Table 2 appear conservative; please justify them with supporting test data.',
        'The paper is well structured and will be valuable to practitioners once the figures are improved.',
        'I recommend adding a short discussion of the limitations and the conditions under which the method does not apply.',
        'The terminology is inconsistent in places; a glossary would help readers from adjacent disciplines.',
    ];

    private const NOTE_SENTENCES = [
        'Author is a long-standing committee member; fast-track the review if reviewers agree.',
        'Check the overlap with the previously published guideline before approval.',
        'Reviewer availability is limited this month, so allow extra time for the second review.',
        'Legal asked that the compliance language in section 5 be confirmed before publication.',
        'Consider cross-indexing this paper under the energy sector as well.',
        'Figures need to be re-exported at a higher resolution before the final PDF is released.',
    ];

    private const DESCRIPTIONS = [
        'Develops and maintains technical standards and recommended practices for its members.',
        'Coordinates expert review of submissions and advises on compliance requirements.',
        'Publishes research manuals and guidance for industry practitioners and regulators.',
        'Brings together engineers from industry and academia to harmonise technical requirements.',
    ];

    public static function title(): string
    {
        return sprintf(fake()->randomElement(self::FORMATS), fake()->randomElement(self::SUBJECTS));
    }

    public static function abstract(): string
    {
        $sentences = fake()->randomElements(self::ABSTRACT_SENTENCES, 5);

        return implode(' ', array_slice($sentences, 0, 3))."\n\n".implode(' ', array_slice($sentences, 3));
    }

    public static function reviewComments(): string
    {
        return implode(' ', fake()->randomElements(self::REVIEW_SENTENCES, fake()->numberBetween(3, 5)));
    }

    public static function confidentialNote(): string
    {
        return fake()->randomElement(self::NOTE_SENTENCES);
    }

    public static function description(): string
    {
        return fake()->randomElement(self::DESCRIPTIONS);
    }

    /** A unique "<Adjective> <Noun>" name for committees and sectors in tests. */
    public static function uniqueName(): string
    {
        $adjectives = ['Applied', 'Advanced', 'Regional', 'Industrial', 'Digital', 'Sustainable', 'Integrated', 'Precision'];
        $nouns = ['Materials', 'Systems', 'Safety', 'Mechanics', 'Networks', 'Infrastructure', 'Processes', 'Energy', 'Controls', 'Testing'];

        $i = fake()->unique()->numberBetween(0, count($adjectives) * count($nouns) - 1);

        return $adjectives[intdiv($i, count($nouns))].' '.$nouns[$i % count($nouns)];
    }
}
