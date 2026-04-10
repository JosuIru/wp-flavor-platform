/**
 * Tests para compresión
 * @vitest-environment node
 */

import { describe, it, expect } from 'vitest';
import {
  compress,
  decompress,
  compressObject,
  decompressObject,
  compressionRatio,
} from './lzCompress.js';

describe('lzCompress', () => {
  describe('compress/decompress síncrono', () => {
    it('debería comprimir y descomprimir string simple', () => {
      const original = 'Hello World';
      const compressed = compress(original);
      const decompressed = decompress(compressed);

      expect(decompressed).toBe(original);
    });

    it('debería comprimir y descomprimir string largo', () => {
      const original = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '.repeat(100);
      const compressed = compress(original);
      const decompressed = decompress(compressed);

      expect(decompressed).toBe(original);
    });

    it('debería manejar string vacío', () => {
      expect(compress('')).toBe('');
      expect(decompress('')).toBe('');
    });

    it('debería manejar caracteres especiales', () => {
      const original = '¡Hola! ñ áéíóú 中文 🎉';
      const compressed = compress(original);
      const decompressed = decompress(compressed);

      expect(decompressed).toBe(original);
    });

    it('debería producir string con prefijo S:', () => {
      const compressed = compress('test');
      expect(compressed.startsWith('S:')).toBe(true);
    });
  });

  describe('compressObject/decompressObject', () => {
    it('debería comprimir y descomprimir objeto simple', () => {
      const original = { name: 'Test', value: 123 };
      const compressed = compressObject(original);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(original);
    });

    it('debería comprimir y descomprimir objeto anidado', () => {
      const original = {
        sections: [
          {
            id: 's1',
            blocks: [
              { id: 'b1', values: { title: 'Test', count: 5 } },
              { id: 'b2', values: { title: 'Another', items: [1, 2, 3] } },
            ],
          },
        ],
        selectedBlockId: 'b1',
      };

      const compressed = compressObject(original);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(original);
    });

    it('debería comprimir y descomprimir arrays', () => {
      const original = [1, 2, 3, 'a', 'b', { nested: true }];
      const compressed = compressObject(original);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(original);
    });

    it('debería manejar valores null', () => {
      const original = { value: null, other: 'test' };
      const compressed = compressObject(original);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(original);
    });

    it('debería manejar valores booleanos', () => {
      const original = { active: true, disabled: false };
      const compressed = compressObject(original);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(original);
    });
  });

  describe('compressionRatio', () => {
    it('debería calcular ratio de compresión', () => {
      const original = 'Test string';
      const compressed = compress(original);
      const ratio = compressionRatio(original, compressed);

      expect(ratio).toBeGreaterThan(0);
    });

    it('debería retornar 1 para inputs vacíos', () => {
      expect(compressionRatio('', '')).toBe(1);
      expect(compressionRatio(null, null)).toBe(1);
    });
  });

  describe('Integración con diffs típicos', () => {
    it('debería comprimir diffs de historial correctamente', () => {
      const typicalDiff = {
        sections: [
          {
            type: 'updateBlockValues',
            sectionId: 'section-123456789',
            blockId: 'block-987654321',
            oldValues: {
              title: 'Old Title',
              subtitle: 'Old Subtitle',
              content: 'Old content that was here before the change',
            },
            newValues: {
              title: 'New Title',
              subtitle: 'Old Subtitle',
              content: 'New content after the user made changes',
            },
          },
        ],
        selectedBlockId: { old: 'block-123', new: 'block-987654321' },
        selectedSectionId: null,
      };

      const compressed = compressObject(typicalDiff);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(typicalDiff);
    });

    it('debería manejar múltiples diffs en secuencia', () => {
      const diffs = Array(20).fill(null).map((_, i) => ({
        sections: [
          {
            type: 'addBlock',
            sectionId: `s${i}`,
            blockId: `b${i}`,
            block: { id: `b${i}`, componentId: 'text', values: { content: `Block ${i}` } },
            index: i,
          },
        ],
        selectedBlockId: { old: i > 0 ? `b${i - 1}` : null, new: `b${i}` },
        selectedSectionId: null,
      }));

      // Comprimir cada diff
      const compressedDiffs = diffs.map(d => compressObject(d));

      // Descomprimir y verificar
      compressedDiffs.forEach((compressed, i) => {
        const decompressed = decompressObject(compressed);
        expect(decompressed).toEqual(diffs[i]);
      });
    });

    it('debería mantener integridad de datos complejos', () => {
      const complexData = {
        sections: [
          {
            id: 'sec-1',
            settings: {
              fullWidth: true,
              backgroundColor: '#ff0000',
              padding: { top: 20, bottom: 30 },
            },
            blocks: [
              {
                id: 'blk-1',
                componentId: 'hero-banner',
                values: {
                  title: 'Título con "comillas" y \'apóstrofes\'',
                  html: '<div class="test">HTML content</div>',
                  items: [
                    { label: 'Item 1', url: 'https://example.com?param=value&other=123' },
                    { label: 'Item 2', url: '/path/to/page' },
                  ],
                },
                variant: 'dark',
              },
            ],
          },
        ],
        selectedBlockId: 'blk-1',
        selectedSectionId: 'sec-1',
      };

      const compressed = compressObject(complexData);
      const decompressed = decompressObject(compressed);

      expect(decompressed).toEqual(complexData);
      expect(decompressed.sections[0].blocks[0].values.title).toBe('Título con "comillas" y \'apóstrofes\'');
      expect(decompressed.sections[0].blocks[0].values.items[0].url).toBe('https://example.com?param=value&other=123');
    });
  });
});
