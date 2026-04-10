/**
 * Tests para useFieldDebounce
 * @vitest-environment node
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock de Vue para tests sin DOM
vi.mock('vue', () => ({
  ref: (value) => ({ value }),
  onUnmounted: () => {},
}));

// Importar después del mock
import { useFieldDebounce, useSingleFieldDebounce } from './useFieldDebounce.js';

describe('useFieldDebounce', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('debouncedUpdate', () => {
    it('debería emitir update después del delay', () => {
      const { debouncedUpdate } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'Hello', emitFn);

      // No debería haberse llamado inmediatamente
      expect(emitFn).not.toHaveBeenCalled();

      // Avanzar el tiempo
      vi.advanceTimersByTime(100);

      // Ahora debería haberse llamado
      expect(emitFn).toHaveBeenCalledWith('title', 'Hello');
      expect(emitFn).toHaveBeenCalledTimes(1);
    });

    it('debería cancelar updates anteriores del mismo campo', () => {
      const { debouncedUpdate } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'H', emitFn);
      debouncedUpdate('title', 'He', emitFn);
      debouncedUpdate('title', 'Hel', emitFn);
      debouncedUpdate('title', 'Hell', emitFn);
      debouncedUpdate('title', 'Hello', emitFn);

      vi.advanceTimersByTime(100);

      // Solo debería emitir el último valor
      expect(emitFn).toHaveBeenCalledTimes(1);
      expect(emitFn).toHaveBeenCalledWith('title', 'Hello');
    });

    it('debería manejar múltiples campos independientemente', () => {
      const { debouncedUpdate } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'Hello', emitFn);
      debouncedUpdate('subtitle', 'World', emitFn);

      vi.advanceTimersByTime(100);

      expect(emitFn).toHaveBeenCalledTimes(2);
      expect(emitFn).toHaveBeenCalledWith('title', 'Hello');
      expect(emitFn).toHaveBeenCalledWith('subtitle', 'World');
    });

    it('debería permitir delay personalizado por llamada', () => {
      const { debouncedUpdate } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'Fast', emitFn, 50);
      debouncedUpdate('subtitle', 'Slow', emitFn, 200);

      vi.advanceTimersByTime(50);
      expect(emitFn).toHaveBeenCalledWith('title', 'Fast');
      expect(emitFn).toHaveBeenCalledTimes(1);

      vi.advanceTimersByTime(150);
      expect(emitFn).toHaveBeenCalledWith('subtitle', 'Slow');
      expect(emitFn).toHaveBeenCalledTimes(2);
    });
  });

  describe('isPending', () => {
    it('debería indicar si hay update pendiente', () => {
      const { debouncedUpdate, isPending } = useFieldDebounce(100);
      const emitFn = vi.fn();

      expect(isPending('title')).toBe(false);

      debouncedUpdate('title', 'Hello', emitFn);
      expect(isPending('title')).toBe(true);

      vi.advanceTimersByTime(100);
      expect(isPending('title')).toBe(false);
    });
  });

  describe('flushAll', () => {
    it('debería emitir todos los updates pendientes inmediatamente', () => {
      const { debouncedUpdate, flushAll } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'Hello', emitFn);
      debouncedUpdate('subtitle', 'World', emitFn);

      expect(emitFn).not.toHaveBeenCalled();

      flushAll(emitFn);

      expect(emitFn).toHaveBeenCalledTimes(2);
      expect(emitFn).toHaveBeenCalledWith('title', 'Hello');
      expect(emitFn).toHaveBeenCalledWith('subtitle', 'World');
    });
  });

  describe('cancelAll', () => {
    it('debería cancelar todos los updates pendientes', () => {
      const { debouncedUpdate, cancelAll } = useFieldDebounce(100);
      const emitFn = vi.fn();

      debouncedUpdate('title', 'Hello', emitFn);
      debouncedUpdate('subtitle', 'World', emitFn);

      cancelAll();

      vi.advanceTimersByTime(100);

      expect(emitFn).not.toHaveBeenCalled();
    });
  });
});

describe('useSingleFieldDebounce', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('debería emitir update después del delay', () => {
    const emitFn = vi.fn();
    const { update } = useSingleFieldDebounce(emitFn, 100);

    update('Hello');

    expect(emitFn).not.toHaveBeenCalled();

    vi.advanceTimersByTime(100);

    expect(emitFn).toHaveBeenCalledWith('Hello');
  });

  it('debería cancelar updates anteriores', () => {
    const emitFn = vi.fn();
    const { update } = useSingleFieldDebounce(emitFn, 100);

    update('H');
    update('He');
    update('Hel');
    update('Hello');

    vi.advanceTimersByTime(100);

    expect(emitFn).toHaveBeenCalledTimes(1);
    expect(emitFn).toHaveBeenCalledWith('Hello');
  });

  it('flush debería emitir inmediatamente', () => {
    const emitFn = vi.fn();
    const { update, flush } = useSingleFieldDebounce(emitFn, 100);

    update('Hello');
    flush();

    expect(emitFn).toHaveBeenCalledWith('Hello');
    expect(emitFn).toHaveBeenCalledTimes(1);

    // No debería emitir de nuevo después del delay
    vi.advanceTimersByTime(100);
    expect(emitFn).toHaveBeenCalledTimes(1);
  });

  it('cancel debería cancelar sin emitir', () => {
    const emitFn = vi.fn();
    const { update, cancel } = useSingleFieldDebounce(emitFn, 100);

    update('Hello');
    cancel();

    vi.advanceTimersByTime(100);

    expect(emitFn).not.toHaveBeenCalled();
  });
});
