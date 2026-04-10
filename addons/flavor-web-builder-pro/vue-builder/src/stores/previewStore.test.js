/**
 * Tests para previewStore
 * @vitest-environment node
 */

/* global globalThis */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

// Mock de fetch
globalThis.fetch = vi.fn();

import { usePreviewStore } from './previewStore';

describe('previewStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('initialize', () => {
    it('debería inicializar con configuración', () => {
      const store = usePreviewStore();
      store.initialize({
        ajaxUrl: 'http://test.com/ajax',
        nonce: 'test-nonce',
        postId: 123,
      });

      expect(store.ajaxUrl).toBe('http://test.com/ajax');
      expect(store.nonce).toBe('test-nonce');
      expect(store.postId).toBe(123);
    });
  });

  describe('hashValues', () => {
    it('debería generar hash de valores', () => {
      const store = usePreviewStore();
      const hash1 = store.hashValues({ title: 'Hello' });
      const hash2 = store.hashValues({ title: 'Hello' });
      const hash3 = store.hashValues({ title: 'World' });

      expect(hash1).toBe(hash2);
      expect(hash1).not.toBe(hash3);
    });

    it('debería manejar valores nulos', () => {
      const store = usePreviewStore();
      const hash = store.hashValues(null);
      expect(hash).toBe('{}');
    });
  });

  describe('isCacheValid', () => {
    it('debería retornar false si no hay cache', () => {
      const store = usePreviewStore();
      expect(store.isCacheValid('block1', {})).toBe(false);
    });

    it('debería retornar true si cache es válido', () => {
      const store = usePreviewStore();
      const values = { title: 'Hello' };

      store.htmlCache['block1'] = {
        html: '<div>Test</div>',
        timestamp: Date.now(),
        valuesHash: store.hashValues(values),
      };

      expect(store.isCacheValid('block1', values)).toBe(true);
    });

    it('debería retornar false si valores han cambiado', () => {
      const store = usePreviewStore();

      store.htmlCache['block1'] = {
        html: '<div>Test</div>',
        timestamp: Date.now(),
        valuesHash: store.hashValues({ title: 'Hello' }),
      };

      expect(store.isCacheValid('block1', { title: 'World' })).toBe(false);
    });

    it('debería retornar false si cache ha expirado', () => {
      const store = usePreviewStore();
      const values = { title: 'Hello' };

      store.htmlCache['block1'] = {
        html: '<div>Test</div>',
        timestamp: Date.now() - (store.cacheMaxAge + 1000),
        valuesHash: store.hashValues(values),
      };

      expect(store.isCacheValid('block1', values)).toBe(false);
    });
  });

  describe('getCached', () => {
    it('debería retornar HTML si cache es válido', () => {
      const store = usePreviewStore();
      const values = { title: 'Hello' };

      store.htmlCache['block1'] = {
        html: '<div>Test</div>',
        timestamp: Date.now(),
        valuesHash: store.hashValues(values),
      };

      expect(store.getCached('block1', values)).toBe('<div>Test</div>');
    });

    it('debería retornar null si cache no es válido', () => {
      const store = usePreviewStore();
      expect(store.getCached('block1', {})).toBeNull();
    });
  });

  describe('getHtml', () => {
    it('debería extraer HTML del cache', () => {
      const store = usePreviewStore();

      store.htmlCache['block1'] = {
        html: '<div>Test</div>',
        timestamp: Date.now(),
        valuesHash: '',
      };

      expect(store.getHtml('block1')).toBe('<div>Test</div>');
    });

    it('debería manejar formato legacy (string directo)', () => {
      const store = usePreviewStore();
      store.htmlCache['block1'] = '<div>Legacy</div>';

      expect(store.getHtml('block1')).toBe('<div>Legacy</div>');
    });

    it('debería retornar string vacío si no hay cache', () => {
      const store = usePreviewStore();
      expect(store.getHtml('block1')).toBe('');
    });
  });

  describe('cleanupDeleted', () => {
    it('debería eliminar cache de bloques eliminados', () => {
      const store = usePreviewStore();

      store.htmlCache['block1'] = { html: '1', timestamp: Date.now(), valuesHash: '' };
      store.htmlCache['block2'] = { html: '2', timestamp: Date.now(), valuesHash: '' };
      store.htmlCache['block3'] = { html: '3', timestamp: Date.now(), valuesHash: '' };

      const cleaned = store.cleanupDeleted(['block1', 'block3']);

      expect(cleaned).toBe(1);
      expect(store.htmlCache['block1']).toBeDefined();
      expect(store.htmlCache['block2']).toBeUndefined();
      expect(store.htmlCache['block3']).toBeDefined();
    });
  });

  describe('cleanupExpired', () => {
    it('debería eliminar cache expirado', () => {
      const store = usePreviewStore();
      const now = Date.now();

      store.htmlCache['valid'] = {
        html: '<div>Valid</div>',
        timestamp: now,
        valuesHash: '',
      };

      store.htmlCache['expired'] = {
        html: '<div>Expired</div>',
        timestamp: now - (store.cacheMaxAge + 1000),
        valuesHash: '',
      };

      const cleaned = store.cleanupExpired();

      expect(cleaned).toBe(1);
      expect(store.htmlCache['valid']).toBeDefined();
      expect(store.htmlCache['expired']).toBeUndefined();
    });
  });

  describe('invalidate', () => {
    it('debería invalidar todo el cache si blockIds es null', () => {
      const store = usePreviewStore();

      store.htmlCache['block1'] = { html: '1', timestamp: Date.now(), valuesHash: '' };
      store.htmlCache['block2'] = { html: '2', timestamp: Date.now(), valuesHash: '' };

      store.invalidate(null);

      expect(Object.keys(store.htmlCache).length).toBe(0);
    });

    it('debería invalidar solo bloques especificados', () => {
      const store = usePreviewStore();

      store.htmlCache['block1'] = { html: '1', timestamp: Date.now(), valuesHash: '' };
      store.htmlCache['block2'] = { html: '2', timestamp: Date.now(), valuesHash: '' };

      store.invalidate(['block1']);

      expect(store.htmlCache['block1']).toBeUndefined();
      expect(store.htmlCache['block2']).toBeDefined();
    });
  });

  describe('cacheStats', () => {
    it('debería calcular estadísticas correctas', () => {
      const store = usePreviewStore();
      const now = Date.now();

      store.htmlCache['valid1'] = { html: '1', timestamp: now, valuesHash: '' };
      store.htmlCache['valid2'] = { html: '2', timestamp: now, valuesHash: '' };
      store.htmlCache['expired'] = {
        html: '3',
        timestamp: now - (store.cacheMaxAge + 1000),
        valuesHash: '',
      };

      const stats = store.cacheStats;

      expect(stats.total).toBe(3);
      expect(stats.valid).toBe(2);
      expect(stats.expired).toBe(1);
    });
  });
});
