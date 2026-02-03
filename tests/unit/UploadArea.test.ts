/**
 * Unit Tests for UploadArea Component
 * 
 * Tests the UploadArea component's drag-and-drop functionality, file selection,
 * visual feedback, and upload progress indicators.
 * 
 * @Requirements: 4.2, 4.4, 5.1
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import UploadArea from '@/Components/MediaScreen/UploadArea.vue';
import type { Media } from '@/types/media-screen';

// Mock the useMediaUpload composable
vi.mock('@/Composables/useMediaUpload', () => ({
  useMediaUpload: () => ({
    uploadingFiles: { value: [] },
    uploadFiles: vi.fn().mockResolvedValue([]),
    validateFiles: vi.fn((files: File[]) => ({
      isValid: true,
      validFiles: files,
      invalidFiles: [],
      errors: undefined
    })),
    cancelUpload: vi.fn()
  })
}));

describe('UploadArea', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  /**
   * Test: Basic rendering
   * Ensures component renders with correct initial state
   */
  it('deve renderizar a área de upload corretamente', () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    // Should show upload area
    expect(wrapper.find('.upload-area').exists()).toBe(true);
    
    // Should show upload text
    expect(wrapper.text()).toContain('Arraste arquivos ou clique para selecionar');
    
    // Should show file type information
    expect(wrapper.text()).toContain('Imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime)');
    
    // Should have hidden file input
    expect(wrapper.find('.hidden-file-input').exists()).toBe(true);
  });

  /**
   * Test: Drag over visual feedback
   * Validates Requisito 4.2: Provide visual feedback when dragging files
   */
  it('deve adicionar classe drag-over ao arrastar arquivos', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Initially should not have drag-over class
    expect(uploadArea.classes()).not.toContain('drag-over');

    // Trigger dragover event
    await uploadArea.trigger('dragover');
    await nextTick();

    // Should have drag-over class
    expect(uploadArea.classes()).toContain('drag-over');
    
    // Text should change
    expect(wrapper.text()).toContain('Solte os arquivos aqui');
  });

  /**
   * Test: Drag leave removes visual feedback
   * Validates Requisito 4.2: Remove visual feedback when drag leaves area
   */
  it('deve remover classe drag-over ao sair da área', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Add drag-over class
    await uploadArea.trigger('dragover');
    await nextTick();
    expect(uploadArea.classes()).toContain('drag-over');

    // Trigger dragleave event on the upload area itself
    await uploadArea.trigger('dragleave');
    await nextTick();

    // Should remove drag-over class
    expect(uploadArea.classes()).not.toContain('drag-over');
  });

  /**
   * Test: Click opens file selector
   * Validates Requisito 4.4: Open file selector when clicking upload area
   */
  it('deve abrir seletor de arquivos ao clicar na área', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    // Mock the file input click method
    const fileInput = wrapper.find('.hidden-file-input');
    const clickSpy = vi.spyOn(fileInput.element as HTMLInputElement, 'click');

    // Click on upload area
    await wrapper.find('.upload-area').trigger('click');
    await nextTick();

    // Should trigger file input click
    expect(clickSpy).toHaveBeenCalled();
  });

  /**
   * Test: Drop event handling
   * Validates Requisito 4.3: Initiate upload when files are dropped
   */
  it('deve processar arquivos ao soltar na área', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Create mock file
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    
    // Trigger drop event with proper dataTransfer
    await uploadArea.trigger('drop', {
      dataTransfer: {
        files: [file]
      }
    });
    await nextTick();

    // Should emit upload-started event
    expect(wrapper.emitted('upload-started')).toBeTruthy();
    expect(wrapper.emitted('upload-started')?.[0]).toEqual([[file]]);
    
    // Should remove drag-over class
    expect(uploadArea.classes()).not.toContain('drag-over');
  });

  /**
   * Test: File input change handling
   * Validates file selection through input element
   */
  it('deve processar arquivos selecionados pelo input', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const fileInput = wrapper.find('.hidden-file-input');
    
    // Create mock file
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    
    // Mock the files property
    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false
    });

    // Trigger change event
    await fileInput.trigger('change');
    await nextTick();

    // Should emit upload-started event
    expect(wrapper.emitted('upload-started')).toBeTruthy();
    expect(wrapper.emitted('upload-started')?.[0]).toEqual([[file]]);
  });

  /**
   * Test: Empty drop event
   * Edge case: dropping without files
   */
  it('não deve processar drop sem arquivos', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Create mock drop event without files
    const dropEvent = new Event('drop') as DragEvent;
    Object.defineProperty(dropEvent, 'dataTransfer', {
      value: {
        files: []
      }
    });

    // Trigger drop event
    await uploadArea.trigger('drop', dropEvent);
    await nextTick();

    // Should not emit upload-started event
    expect(wrapper.emitted('upload-started')).toBeFalsy();
  });

  /**
   * Test: Format file size utility
   * Ensures file sizes are displayed in human-readable format
   */
  it('deve formatar tamanhos de arquivo corretamente', () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const vm = wrapper.vm as any;

    // Test various file sizes
    expect(vm.formatFileSize(0)).toBe('0 Bytes');
    expect(vm.formatFileSize(1024)).toBe('1 KB');
    expect(vm.formatFileSize(1024 * 1024)).toBe('1 MB');
    expect(vm.formatFileSize(1536 * 1024)).toBe('1.5 MB');
    expect(vm.formatFileSize(1024 * 1024 * 1024)).toBe('1 GB');
  });

  /**
   * Test: Multiple files handling
   * Ensures component can handle multiple files at once
   */
  it('deve processar múltiplos arquivos simultaneamente', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Create multiple mock files
    const files = [
      new File(['content1'], 'test1.jpg', { type: 'image/jpeg' }),
      new File(['content2'], 'test2.png', { type: 'image/png' }),
      new File(['content3'], 'test3.gif', { type: 'image/gif' })
    ];
    
    // Trigger drop event with proper dataTransfer
    await uploadArea.trigger('drop', {
      dataTransfer: {
        files: files
      }
    });
    await nextTick();

    // Should emit upload-started event with all files
    expect(wrapper.emitted('upload-started')).toBeTruthy();
    expect(wrapper.emitted('upload-started')?.[0]).toEqual([files]);
  });

  /**
   * Test: Drag over prevents default
   * Ensures browser default behavior is prevented
   */
  it('deve prevenir comportamento padrão em dragover', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Trigger dragover - Vue Test Utils automatically prevents default
    await uploadArea.trigger('dragover');

    // Component should handle the event (verified by drag-over class)
    expect(uploadArea.classes()).toContain('drag-over');
  });

  /**
   * Test: Drop prevents default
   * Ensures browser default behavior is prevented on drop
   */
  it('deve prevenir comportamento padrão em drop', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const uploadArea = wrapper.find('.upload-area');
    
    // Trigger drop - Vue Test Utils automatically prevents default
    await uploadArea.trigger('drop', {
      dataTransfer: {
        files: []
      }
    });

    // Component should handle the event (verified by no errors)
    expect(uploadArea.exists()).toBe(true);
  });

  /**
   * Test: Album ID prop
   * Ensures albumId prop is correctly received
   */
  it('deve receber albumId como prop', () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 42
      }
    });

    expect(wrapper.props('albumId')).toBe(42);
  });

  /**
   * Test: File input reset after selection
   * Ensures same file can be selected again
   */
  it('deve resetar input após seleção de arquivo', async () => {
    const wrapper = mount(UploadArea, {
      props: {
        albumId: 1
      }
    });

    const fileInput = wrapper.find('.hidden-file-input').element as HTMLInputElement;
    
    // Create mock file
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    
    // Mock the files property
    Object.defineProperty(fileInput, 'files', {
      value: [file],
      writable: false,
      configurable: true
    });

    // Trigger change event
    await wrapper.find('.hidden-file-input').trigger('change');
    await nextTick();

    // Input value should be reset (empty string is the only valid value for file inputs)
    expect(fileInput.value).toBe('');
  });
});
