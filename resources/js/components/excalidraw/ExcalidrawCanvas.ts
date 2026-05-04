import { Excalidraw } from '@excalidraw/excalidraw';
import '@excalidraw/excalidraw/index.css';
import React from 'react';
import type { ExcalidrawScene, JsonValue } from '@/types';

type Props = {
    initialData?: ExcalidrawScene | null;
    name: string;
    readonly: boolean;
    onChange: (scene: ExcalidrawScene) => void;
};

function cloneJson<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

export function ExcalidrawCanvas({
    initialData,
    name,
    readonly,
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
                  },
                  files: initialData.files ?? {},
              }
            : {
                  appState: {
                      name,
                  },
              },
        name,
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
                appState: cloneJson(appState) as Record<string, JsonValue>,
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
