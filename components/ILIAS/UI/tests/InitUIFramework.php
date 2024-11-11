<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * This is more or less a copy of the removed InitUIFramework file inside the Init component.
 * We keep it here since some unit tests depend on a fully initialised UI framework, which
 * is populated in the global DIC using the old offsets.
 *
 * @deprecated don't use this. we should try to find a better way to perform rendering
 *             tests that rely on a fully initialised UI framework.
 */
class InitUIFramework
{
    public function init(\ILIAS\DI\Container $c): void
    {
        $c["ui.factory"] = function ($c) {
            return new ILIAS\UI\Implementation\Factory(
                $c["ui.factory.counter"],
                $c["ui.factory.button"],
                $c["ui.factory.listing"],
                $c["ui.factory.image"],
                new \ILIAS\UI\Implementation\Component\Player\Factory(),
                $c["ui.factory.panel"],
                $c["ui.factory.modal"],
                $c["ui.factory.progress"],
                $c["ui.factory.dropzone"],
                $c["ui.factory.popover"],
                $c["ui.factory.divider"],
                $c["ui.factory.link"],
                $c["ui.factory.dropdown"],
                $c["ui.factory.item"],
                $c["ui.factory.viewcontrol"],
                $c["ui.factory.chart"],
                $c["ui.factory.input"],
                $c["ui.factory.table"],
                $c["ui.factory.messagebox"],
                $c["ui.factory.card"],
                $c["ui.factory.layout"],
                $c["ui.factory.maincontrols"],
                $c["ui.factory.tree"],
                $c["ui.factory.menu"],
                $c["ui.factory.symbol"],
                $c["ui.factory.toast"],
                $c["ui.factory.legacy"],
                $c["ui.factory.launcher"],
                $c["ui.factory.entity"],
                $c["ui.factory.prompt"],
                $c["ui.factory.navigation"],
            );
        };
        $c["ui.upload_limit_resolver"] = function ($c) {
            return new \ILIAS\UI\Implementation\Component\Input\UploadLimitResolver(
                new class() implements ILIAS\UI\Component\Input\Field\PhpUploadLimit {
                    public function getPhpUploadLimitInBytes(): int
                    {
                        return 0;
                    }
                },
                new class() implements ILIAS\UI\Component\Input\Field\GlobalUploadLimit {
                    public function getGlobalUploadLimitInBytes(): ?int
                    {
                        return null;
                    }
                },
            );
        };
        $c["ui.data_factory"] = function ($c) {
            return new ILIAS\Data\Factory();
        };
        $c["ui.signal_generator"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\SignalGenerator();
        };
        $c["ui.factory.counter"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Counter\Factory();
        };
        $c["ui.factory.button"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Button\Factory();
        };
        $c["ui.factory.listing"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Listing\Factory(
                new ILIAS\UI\Implementation\Component\Listing\Workflow\Factory(),
                new ILIAS\UI\Implementation\Component\Listing\CharacteristicValue\Factory(),
                new ILIAS\UI\Implementation\Component\Listing\Entity\Factory(),
            );
        };
        $c["ui.factory.image"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Image\Factory();
        };
        $c["ui.factory.panel"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Panel\Factory(
                $c["ui.factory.panel.listing"],
                new ILIAS\UI\Implementation\Component\Panel\Secondary\Factory(),
            );
        };
        $c["ui.factory.interruptive_item"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Modal\InterruptiveItem\Factory();
        };
        $c["ui.factory.modal"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Modal\Factory(
                $c["ui.signal_generator"],
                $c["ui.factory.interruptive_item"],
                $c["ui.factory.input.field"],
            );
        };
        $c["ui.factory.progress.refresh_interval"] = static fn (\ILIAS\DI\Container $c) =>
        new class() implements \ILIAS\UI\Component\Progress\AsyncRefreshInterval {
            public function getRefreshIntervalInMs(): int
            {
                return 1_000;
            }
        };
        $c["ui.factory.progress"] = static fn (\ILIAS\DI\Container $c) =>
        new \ILIAS\UI\Implementation\Component\Progress\Factory(
            $c["ui.factory.progress.refresh_interval"],
            $c["ui.signal_generator"],
            $c["ui.factory.progress.state"],
        );
        $c["ui.factory.progress.state"] = static fn (\ILIAS\DI\Container $c) =>
        new \ILIAS\UI\Implementation\Component\Progress\State\Factory(
            new \ILIAS\UI\Implementation\Component\Progress\State\Bar\Factory(),
        );
        $c["ui.factory.dropzone"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Dropzone\Factory($c["ui.factory.dropzone.file"]);
        };
        $c["ui.factory.popover"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Popover\Factory($c["ui.signal_generator"]);
        };
        $c["ui.factory.divider"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Divider\Factory();
        };
        $c["ui.factory.link"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Link\Factory();
        };
        $c["ui.factory.dropdown"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Dropdown\Factory();
        };
        $c["ui.factory.item"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Item\Factory();
        };
        $c["ui.factory.toast"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Toast\Factory($c["ui.signal_generator"]);
        };
        $c["ui.factory.viewcontrol"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\ViewControl\Factory(
                $c["ui.signal_generator"]
            );
        };
        $c["ui.factory.chart"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Chart\Factory(
                $c["ui.factory.progressmeter"],
                $c["ui.factory.bar"]
            );
        };
        $c["ui.factory.input"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Factory(
                $c["ui.signal_generator"],
                $c["ui.factory.input.field"],
                $c["ui.factory.input.container"],
                $c["ui.factory.input.viewcontrol"]
            );
        };
        $c["ui.factory.table"] = function ($c) {
            $data_row_builder = new ILIAS\UI\Implementation\Component\Table\DataRowBuilder();
            $ordering_row_builder = new ILIAS\UI\Implementation\Component\Table\OrderingRowBuilder();
            return new ILIAS\UI\Implementation\Component\Table\Factory(
                $c["ui.signal_generator"],
                $c['ui.factory.input.viewcontrol'],
                $c['ui.factory.input.container.viewcontrol'],
                $c["ui.data_factory"],
                $c["ui.factory.table.column"],
                $c["ui.factory.table.action"],
                $c["ui.storage"],
                $data_row_builder,
                $ordering_row_builder
            );
        };
        $c["ui.factory.table.column"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Table\Column\Factory(
                $c["lng"]
            );
        };
        $c["ui.factory.table.action"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Table\Action\Factory();
        };
        $c["ui.factory.messagebox"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\MessageBox\Factory();
        };
        $c["ui.factory.card"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Card\Factory();
        };
        $c["ui.factory.layout"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Layout\Factory(
                new ILIAS\UI\Implementation\Component\Layout\Page\Factory(),
                new ILIAS\UI\Implementation\Component\Layout\Alignment\Factory(),
            );
        };
        $c["ui.factory.maincontrols.slate"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\MainControls\Slate\Factory(
                $c['ui.signal_generator'],
                $c['ui.factory.counter'],
                $c["ui.factory.symbol"]
            );
        };
        $c["ui.factory.maincontrols"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\MainControls\Factory(
                $c['ui.signal_generator'],
                $c['ui.factory.maincontrols.slate']
            );
        };
        $c["ui.factory.menu"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Menu\Factory(
                $c['ui.signal_generator']
            );
        };
        $c["ui.factory.symbol.glyph"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Symbol\Glyph\Factory();
        };
        $c["ui.factory.symbol.icon"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Symbol\Icon\Factory();
        };
        $c["ui.factory.symbol.avatar"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Symbol\Avatar\Factory();
        };
        $c["ui.factory.symbol"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Symbol\Factory(
                $c["ui.factory.symbol.icon"],
                $c["ui.factory.symbol.glyph"],
                $c["ui.factory.symbol.avatar"]
            );
        };
        $c["ui.factory.progressmeter"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Chart\ProgressMeter\Factory();
        };
        $c["ui.factory.bar"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Chart\Bar\Factory();
        };
        $c["ui.factory.input.field"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Field\Factory(
                $c["ui.upload_limit_resolver"],
                $c["ui.signal_generator"],
                $c["ui.data_factory"],
                $c["refinery"],
                $c["lng"]
            );
        };
        $c["ui.factory.input.container"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Container\Factory(
                $c["ui.factory.input.container.form"],
                $c["ui.factory.input.container.filter"],
                $c["ui.factory.input.container.viewcontrol"]
            );
        };
        $c["ui.factory.input.container.form"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Container\Form\Factory(
                $c["ui.factory.input.field"],
                $c["ui.signal_generator"]
            );
        };
        $c["ui.factory.input.container.filter"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Container\Filter\Factory(
                $c["ui.signal_generator"],
                $c["ui.factory.input.field"]
            );
        };
        $c["ui.factory.input.container.viewcontrol"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\Container\ViewControl\Factory(
                $c["ui.signal_generator"],
                $c["ui.factory.input.viewcontrol"],
            );
        };
        $c["ui.factory.input.viewcontrol"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Input\ViewControl\Factory(
                $c["ui.factory.input.field"],
                $c["ui.data_factory"],
                $c["refinery"],
                $c["ui.signal_generator"],
                $c["lng"],
            );
        };
        $c["ui.factory.dropzone.file"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Dropzone\File\Factory(
                $c["ui.signal_generator"],
                $c["ui.factory.input.field"],
            );
        };
        $c["ui.factory.panel.listing"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Panel\Listing\Factory();
        };
        $c["ui.renderer"] = function ($c) {
            return new ILIAS\UI\Implementation\DefaultRenderer(
                $c["ui.component_renderer_loader"],
                $c["ui.javascript_binding"],
                $c["lng"],
            );
        };
        $c["ui.component_renderer_loader"] = function ($c) {
            return new ILIAS\UI\Implementation\Render\LoaderCachingWrapper(
                new ILIAS\UI\Implementation\Render\LoaderResourceRegistryWrapper(
                    $c["ui.resource_registry"],
                    new ILIAS\UI\Implementation\Render\FSLoader(
                        new ILIAS\UI\Implementation\Render\DefaultRendererFactory(
                            $c["ui.factory"],
                            $c["ui.template_factory"],
                            $c["lng"],
                            $c["ui.javascript_binding"],
                            $c["ui.pathresolver"],
                            $c["ui.data_factory"],
                            $c["help.text_retriever"],
                            $c["ui.upload_limit_resolver"]
                        ),
                        new ILIAS\UI\Implementation\Component\Symbol\Glyph\GlyphRendererFactory(
                            $c["ui.factory"],
                            $c["ui.template_factory"],
                            $c["lng"],
                            $c["ui.javascript_binding"],
                            $c["ui.pathresolver"],
                            $c["ui.data_factory"],
                            $c["help.text_retriever"],
                            $c["ui.upload_limit_resolver"]
                        ),
                        new ILIAS\UI\Implementation\Component\Input\Field\FieldRendererFactory(
                            $c["ui.factory"],
                            $c["ui.template_factory"],
                            $c["lng"],
                            $c["ui.javascript_binding"],
                            $c["ui.pathresolver"],
                            $c["ui.data_factory"],
                            $c["help.text_retriever"],
                            $c["ui.upload_limit_resolver"]
                        ),
                        new ILIAS\UI\Implementation\Component\MessageBox\MessageBoxRendererFactory(
                            $c["ui.factory"],
                            $c["ui.template_factory"],
                            $c["lng"],
                            $c["ui.javascript_binding"],
                            $c["ui.pathresolver"],
                            $c["ui.data_factory"],
                            $c["help.text_retriever"],
                            $c["ui.upload_limit_resolver"]
                        ),
                        new ILIAS\UI\Implementation\Component\Input\Container\Form\FormRendererFactory(
                            $c["ui.factory"],
                            $c["ui.template_factory"],
                            $c["lng"],
                            $c["ui.javascript_binding"],
                            $c["ui.pathresolver"],
                            $c["ui.data_factory"],
                            $c["help.text_retriever"],
                            $c["ui.upload_limit_resolver"]
                        ),
                    )
                )
            );
        };
        $c["ui.template_factory"] = function ($c) {
            return new ILIAS\UI\Implementation\Render\ilTemplateWrapperFactory($c["tpl"]);
        };
        $c["ui.resource_registry"] = function ($c) {
            return new ILIAS\UI\Implementation\Render\ilResourceRegistry($c["tpl"]);
        };
        $c["ui.javascript_binding"] = function ($c) {
            return new ILIAS\UI\Implementation\Render\ilJavaScriptBinding($c["tpl"]);
        };

        $c["ui.factory.tree"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Tree\Factory(
                new ILIAS\UI\Implementation\Component\Tree\Node\Factory(),
            );
        };

        $c["ui.factory.legacy"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Legacy\Factory($c["ui.signal_generator"]);
        };

        $c["ui.pathresolver"] = function ($c): ILIAS\UI\Implementation\Render\ImagePathResolver {
            return new ilImagePathResolver();
        };

        $c["ui.factory.launcher"] = function ($c): ILIAS\UI\Implementation\Component\Launcher\Factory {
            return new ILIAS\UI\Implementation\Component\Launcher\Factory(
                $c["ui.factory.modal"]
            );
        };

        $c["ui.factory.entity"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Entity\Factory();
        };

        $c["ui.factory.prompt"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Prompt\Factory($c["ui.signal_generator"]);
        };

        $c["ui.factory.navigation"] = function ($c) {
            return new ILIAS\UI\Implementation\Component\Navigation\Factory(
                $c["ui.data_factory"],
                $c["refinery"],
                $c["ui.storage"],
            );
        };

        // currently this is will be a session storage because we cannot store
        // data on the client, see https://mantis.ilias.de/view.php?id=38503.
        $c["ui.storage"] = function ($c): ArrayAccess {
            return new class() implements ArrayAccess {
                public function offsetExists(mixed $offset): bool
                {
                    return ilSession::has($offset);
                }
                public function offsetGet(mixed $offset): mixed
                {
                    return ilSession::get($offset);
                }
                public function offsetSet(mixed $offset, mixed $value): void
                {
                    if (!is_string($offset)) {
                        throw new InvalidArgumentException('Offset needs to be of type string.');
                    }
                    ilSession::set($offset, $value);
                }
                public function offsetUnset(mixed $offset): void
                {
                    ilSession::clear($offset);
                }
            };
        };
    }
}
