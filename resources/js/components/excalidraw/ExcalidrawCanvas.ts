import { Excalidraw } from '@excalidraw/excalidraw';
import '@excalidraw/excalidraw/index.css';
import React from 'react';
import type { ExcalidrawScene, JsonValue } from '@/types';

type Props = {
    initialData?: ExcalidrawScene | null;
    name: string;
    readonly: boolean;
    theme?: 'light' | 'dark';
    onChange: (scene: ExcalidrawScene) => void;
};

function cloneJson<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

function stripSelectionState(
    appState: Record<string, JsonValue>,
): Record<string, JsonValue> {
    const cleaned = { ...appState };

    delete cleaned['selectedElementIds'];
    delete cleaned['selectedGroupIds'];
    delete cleaned['editingGroupId'];
    delete cleaned['editingLinearElement'];
    delete cleaned['editingElement'];
    delete cleaned['resizingElement'];
    delete cleaned['draggingElement'];
    delete cleaned['hoverElement'];

    return cleaned;
}

export function ExcalidrawCanvas({
    initialData,
    name,
    readonly,
    theme = 'light',
    onChange,
}: Props): React.ReactElement {
    const ExcalidrawComponent = Excalidraw as React.ComponentType<
        Record<string, unknown>
    >;

    return React.createElement(ExcalidrawComponent, {
        initialData: initialData
            ? {
                  elements: initialData.elements ?? [],
                  appState: {
                      ...(initialData.appState ?? {}),
                      name,
                      zoom: 1,
                  },
                  files: initialData.files ?? {},
                  scrollToContent: true,
              }
            : {
                  appState: {
                      name,
                      zoom: 1,
                  },
              },
        name,
        theme,
        viewModeEnabled: readonly,
        onChange: (
            elements: readonly unknown[],
            appState: unknown,
            files: unknown,
        ) => {
            onChange({
                type: 'excalidraw',
                version: 2,
                source: window.location.origin,
                elements: cloneJson(elements) as Record<string, JsonValue>[],
                appState: stripSelectionState(
                    cloneJson(appState) as Record<string, JsonValue>,
                ),
                files: cloneJson(files) as Record<string, JsonValue>,
            });
        },
        UIOptions: {
            canvasActions: {
                saveToActiveFile: false,
                loadScene: false,
            },
        },
    });
}
