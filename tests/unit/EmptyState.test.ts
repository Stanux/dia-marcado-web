/**
 * Unit Tests for EmptyState Component
 * 
 * Tests the EmptyState component's rendering and behavior for different empty state types.
 * 
 * @Requirements: 8.1, 8.2, 8.3
 */

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import EmptyState from '@/Components/MediaScreen/EmptyState.vue';

describe('EmptyState.vue', () => {
  describe('Rendering for type "no-albums"', () => {
    it('deve renderizar título correto para no-albums', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      expect(wrapper.find('.empty-title').text()).toBe('Nenhum álbum criado');
    });

    it('deve renderizar mensagem orientadora para no-albums', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const message = wrapper.find('.empty-message').text();
      expect(message).toContain('Comece criando seu primeiro álbum');
      expect(message).toContain('organizar suas fotos e vídeos');
    });

    it('deve renderizar ícone para no-albums', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      expect(wrapper.find('.empty-icon').text()).toBe('📁');
    });

    it('deve renderizar botão de ação "Criar primeiro álbum"', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const button = wrapper.find('.empty-action');
      expect(button.exists()).toBe(true);
      expect(button.text()).toBe('Criar primeiro álbum');
    });

    it('deve emitir evento create-album ao clicar no botão', async () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      await wrapper.find('.empty-action').trigger('click');

      expect(wrapper.emitted('create-album')).toBeTruthy();
      expect(wrapper.emitted('create-album')).toHaveLength(1);
    });
  });

  describe('Rendering for type "no-media"', () => {
    it('deve renderizar título correto para no-media', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      expect(wrapper.find('.empty-title').text()).toBe('Nenhuma mídia neste álbum');
    });

    it('deve renderizar mensagem com instrução para upload', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      const message = wrapper.find('.empty-message').text();
      expect(message).toContain('Faça upload de fotos e vídeos');
      expect(message).toContain('preencher este álbum');
    });

    it('deve renderizar ícone para no-media', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      expect(wrapper.find('.empty-icon').text()).toBe('🖼️');
    });

    it('deve renderizar botão de ação "Fazer upload"', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      const button = wrapper.find('.empty-action');
      expect(button.exists()).toBe(true);
      expect(button.text()).toBe('Fazer upload');
    });

    it('deve emitir evento upload-media ao clicar no botão', async () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      await wrapper.find('.empty-action').trigger('click');

      expect(wrapper.emitted('upload-media')).toBeTruthy();
      expect(wrapper.emitted('upload-media')).toHaveLength(1);
    });
  });

  describe('Action button presence', () => {
    it('deve sempre exibir botão de ação para no-albums', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      expect(wrapper.find('.empty-action').exists()).toBe(true);
    });

    it('deve sempre exibir botão de ação para no-media', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      expect(wrapper.find('.empty-action').exists()).toBe(true);
    });
  });

  describe('Component structure', () => {
    it('deve ter estrutura correta com todos os elementos', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      expect(wrapper.find('.empty-state').exists()).toBe(true);
      expect(wrapper.find('.empty-icon').exists()).toBe(true);
      expect(wrapper.find('.empty-title').exists()).toBe(true);
      expect(wrapper.find('.empty-message').exists()).toBe(true);
      expect(wrapper.find('.empty-action').exists()).toBe(true);
    });

    it('deve aplicar classes CSS corretas', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const container = wrapper.find('.empty-state');
      expect(container.exists()).toBe(true);
    });
  });

  describe('Accessibility', () => {
    it('deve ter título como heading h3', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const title = wrapper.find('h3.empty-title');
      expect(title.exists()).toBe(true);
    });

    it('deve ter mensagem como parágrafo', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      const message = wrapper.find('p.empty-message');
      expect(message.exists()).toBe(true);
    });

    it('deve ter botão com texto descritivo', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const button = wrapper.find('button.empty-action');
      expect(button.exists()).toBe(true);
      expect(button.text().length).toBeGreaterThan(0);
    });
  });

  describe('Visual feedback', () => {
    it('deve ter ícone visível', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-albums' }
      });

      const icon = wrapper.find('.empty-icon');
      expect(icon.text().length).toBeGreaterThan(0);
    });

    it('deve ter mensagem legível', () => {
      const wrapper = mount(EmptyState, {
        props: { type: 'no-media' }
      });

      const message = wrapper.find('.empty-message');
      expect(message.text().length).toBeGreaterThan(10);
    });
  });
});
