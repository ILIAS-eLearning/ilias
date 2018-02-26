<?php

/* Copyright (c) 2016 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Panel;

use \ILIAS\UI\Component\Component as Component;
/**
 * This is how the factory for UI elements looks. This should provide access
 * to all UI elements at some point.
 */
interface Factory {

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *      Standard Panels are used in the Center Content section to group content.
	 *   composition: >
	 *      Standard Panels consist of a title and a content section. The
	 *      structure of this content might be varying from Standard
	 *      Panel to Standard Panel. Standard Panels may contain Sub Panels.
	 *   rivals:
	 *      Cards: >
	 *        Often Cards are used in Decks to display multiple uniformly structured chunks of Data horizontally and vertically.
	 *
	 * rules:
	 *   usage:
	 *      1: In Forms Standard Panels MUST be used to group different sections into Form Parts.
	 *      2: Standard Panels SHOULD be used in the Center Content as primary Container for grouping content of varying content.
	 * ---
	 * @param string $title
	 * @param Component[]|Component
	 * @return \ILIAS\UI\Component\Panel\Standard
	 */
	public function standard($title,$content);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *       Sub Panels are used to structure the content of Standard panels further into titled sections.
	 *   composition: >
	 *       Sub Panels consist of a title and a content section. They may contain a Card on their right side to display
	 *       meta information about the content displayed.
	 *   rivals:
	 *      Standard Panel: >
	 *        The Standard Panel might contain a Sub Panel.
	 *      Card: >
	 *        The Sub Panels may contain one Card.
	 *
	 * rules:
	 *   usage:
	 *      1: Sub Panels MUST only be inside Standard Panels
	 *   composition:
	 *      1: Sub Panels MUST NOT contain Sub Panels or Standard Panels as content.
	 * ---
	 * @param string $title
	 * @param Component[]|Component
	 * @return \ILIAS\UI\Component\Panel\Sub
	 */
	public function sub($title,$content);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *       Report Panels display user-generated data combining text in lists, tables and sometimes  charts.
	 *       Report Panels always draw from two distinct sources: the structure / scaffolding of the Report Panels
	 *       stems from user-generated content (i.e a question of a survey, a competence with levels) and is
	 *       filled with user-generated content harvested by that very structure (i.e. participants’ answers to
	 *       the question, self-evaluation of competence).
	 *   composition: >
	 *       They are composed of a Standard Panel which contains several Sub
	 *       Panels. They might also contain
	 *       a card to display information meta information in their first block.
	 *   effect: >
	 *       Report Panels are predominantly used for displaying data. They may however comprise links or buttons.
	 *   rivals:
	 *      Standard Panels: >
	 *        The Report Panels contains sub panels used to structure information.
	 *
	 * rules:
	 *   usage:
	 *      1: >
	 *         Report Panels SHOULD be used when user generated content of two sources (i.e results, guidelines in a template)
	 *         is to be displayed alongside each other.
	 *   interaction:
	 *      1: Links MAY open new views.
	 *      2: Buttons MAY trigger actions or inline editing.
	 * ---
	 * @param string $title
	 * @param \ILIAS\UI\Component\Panel\Sub[] $sub_panels
	 * @return \ILIAS\UI\Component\Panel\Report
	 */
	public function report($title,$sub_panels);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *       Listing Panels are used to list items following all one
	 *       single template.
	 *   composition: >
	 *       Listing Panels are composed of several titled Item Groups.
	 *       They further may contain a filter.
	 *   effect: >
	 *       The List Items of Listing Panels may contain a dropdown
	 *       offering options to interact with the item. Further Listing Panels
	 *       may be filtered and the number of sections or items to be displayed
	 *       may be configurable.
	 *   rivals:
	 *      Report Panels: >
	 *        Report Panels contain sections as Sub Panels each displaying
	 *        different aspects of one item.
	 *
	 * rules:
	 *   usage:
	 *      1: >
	 *         Listing Panels SHOULD be used, if a large number of items using
	 *         the same template are to be displayed in an inviting way
	 *         not using a Table.
	 * ---
	 * @return \ILIAS\UI\Component\Panel\Listing\Factory
	 */
	public function listing();

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *       A Sticky Panel holds information or functionality that is presented in parallel to main
	 *       screens and workflows and persists over a longer time.
	 *   composition: >
	 *       A sticky panel can hold one or multiple views which can be selected by a Dropdown.
	 *       Views can be closed by a Close Glyph.
	 *   effect: >
	 *       Only one view will be presented at a time. Other views will be selectable via Dropdown.
	 *       The last view being added to the panel will be the current view, if not
	 *       changed by the user via Dropdown. If all views are closed, the whole panel will
	 *       be removed. The panel can be collapsed and expanded as a whole. It will be presented
	 *       as a fixed area under the top bar. If collapsed the title of the last opened view
	 *       will persist in the collapsed bar under the top bar.
	 *       The Sticky Panel will be presented on top of the right column (e.g. side blocks) of the main view.
	 *       If no right column is currently active, an empty one will be displayed under the Sticky Panel
	 *       to push the content of the main content column to the left (and ensure its full visibility).
	 *       The right edge of the tabs bar and the header actions will also be pushed to the left to ensure
	 *       its full functionality.
	 * rules:
	 *   usage:
	 *      1: Usually rendering is done by by the Standard Template. Consumer code SHOULD NOT render
	 *         the Sticky Panel.
	 *   composition:
	 *      1: Consumer code CAN add single views to the Sticky Panel.
	 * ---
	 * @param \ILIAS\UI\Component\Panel\StickyView[]
	 * @return \ILIAS\UI\Component\Panel\Sticky
	 */
	public function sticky(array $views);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *       A Sticky View hold one view in a Sticky Panel. Views are currently the online help
	 *       and the exercise instrucion view.
	 *   composition: >
	 *       A sticky panel can hold one or multiple views which can be selected by a Dropdown.
	 *       Views can be closed by a Close Glyph.
	 *
	 * rules:
	 *   usage:
	 *      1: Sticky Views should be used for information or tool-like purpose where the view needs
	 *         to persist over multiple requests. I SHOULD not be used for navigational purposes only.
	 *         Sticky Views SHOULD usually be added using a function of the Standard Template.
	 *   composition:
	 *      1: A view consists of a title and content. Since the content can be presented by any
	 *         kitchen sink currently, each Sticky View needs to be confirmed by the Jour Fixe. The
	 *         title is used in the Heading of the Sticky Panel, if a view is the current view. The
	 *         title is also used in the Dropdown of the Sticky Panel to allow switching to a
	 *         particular view.
	 * ---
	 * @param string
	 * @param \ILIAS\UI\Component\Component[]
	 * @return \ILIAS\UI\Component\Panel\StickyView
	 */
	public function stickyView(string $title, array $content): \ILIAS\UI\Component\Panel\StickyView;

}
