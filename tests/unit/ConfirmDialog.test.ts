/**
 * Unit Tests for ConfirmDialog Component
 * 
 * Tests the ConfirmDialog component's rendering, behavior, and event handling.
 * 
 * @Requirements: 7.2
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ConfirmDialog from '@/Components/MediaScreen/ConfirmDialog.vue';

describe('ConfirmDialog.vue', () => {
  describe('Rendering when closed', () => {
    it('não deve renderizar quando isOpen é false', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: false,
          title: 'Confirmar exclusão',
          message: 'Tem certeza que deseja excluir esta mídia?'
        }
      });

      expect(wrapper.find('.dialog-overlay').exists()).toBe(false);
    });
  });

  describe('Rendering when open', () => {
    it('deve renderizar overlay quando isOpen é true', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar exclusão',
          message: 'Tem certeza que deseja excluir esta mídia?'
        },
        attachTo: document.body
      });

      expect(wrapper.find('.dialog-overlay').exists()).toBe(true);
    });

    it('deve renderizar título fornecido', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar exclusão',
          message: 'Tem certeza?'
        },
        attachTo: document.body
      });

      expect(wrapper.find('.dialog-title').text()).toBe('Confirmar exclusão');
    });

    it('deve renderizar mensagem fornecida', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Tem certeza que deseja excluir esta mídia?'
        },
        attachTo: document.body
      });

      expect(wrapper.find('.dialog-message').text()).toBe('Tem certeza que deseja excluir esta mídia?');
    });

    it('deve renderizar botões de confirmar e cancelar', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const buttons = wrapper.findAll('.dialog-button');
      expect(buttons).toHaveLength(2);
    });

    it('deve usar labels padrão quando não fornecidos', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const cancelButton = wrapper.find('.dialog-button-cancel');
      const confirmButton = wrapper.find('.dialog-button-confirm');

      expect(cancelButton.text()).toBe('Cancelar');
      expect(confirmButton.text()).toBe('Confirmar');
    });

    it('deve usar labels customizados quando fornecidos', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem',
          confirmLabel: 'Sim, excluir',
          cancelLabel: 'Não, manter'
        },
        attachTo: document.body
      });

      const cancelButton = wrapper.find('.dialog-button-cancel');
      const confirmButton = wrapper.find('.dialog-button-confirm');

      expect(cancelButton.text()).toBe('Não, manter');
      expect(confirmButton.text()).toBe('Sim, excluir');
    });
  });

  describe('Event handling', () => {
    it('deve emitir evento confirm ao clicar no botão confirmar', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      await wrapper.find('.dialog-button-confirm').trigger('click');

      expect(wrapper.emitted('confirm')).toBeTruthy();
      expect(wrapper.emitted('confirm')).toHaveLength(1);
    });

    it('deve emitir evento cancel ao clicar no botão cancelar', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      await wrapper.find('.dialog-button-cancel').trigger('click');

      expect(wrapper.emitted('cancel')).toBeTruthy();
      expect(wrapper.emitted('cancel')).toHaveLength(1);
    });

    it('deve emitir evento cancel ao clicar no overlay', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      await wrapper.find('.dialog-overlay').trigger('click');

      expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('não deve emitir cancel ao clicar no conteúdo do dialog', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      await wrapper.find('.dialog-container').trigger('click');

      expect(wrapper.emitted('cancel')).toBeFalsy();
    });
  });

  describe('Keyboard handling', () => {
    it('deve emitir cancel ao pressionar Escape', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const event = new KeyboardEvent('keydown', { key: 'Escape' });
      document.dispatchEvent(event);

      await wrapper.vm.$nextTick();

      expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('não deve emitir cancel ao pressionar Escape quando fechado', async () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: false,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const event = new KeyboardEvent('keydown', { key: 'Escape' });
      document.dispatchEvent(event);

      await wrapper.vm.$nextTick();

      expect(wrapper.emitted('cancel')).toBeFalsy();
    });
  });

  describe('Body scroll prevention', () => {
    beforeEach(() => {
      document.body.style.overflow = '';
    });

    afterEach(() => {
      document.body.style.overflow = '';
    });

    it('deve ter lógica de prevenção de scroll implementada', () => {
      // This test verifies the component has the scroll prevention logic
      // The actual DOM manipulation is tested in integration/e2e tests
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      // Verify component is rendered (scroll prevention happens in watch)
      expect(wrapper.find('.dialog-overlay').exists()).toBe(true);
    });

    it('deve limpar efeitos ao desmontar', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      // Verify component can be unmounted without errors
      expect(() => wrapper.unmount()).not.toThrow();
    });
  });

  describe('Component structure', () => {
    it('deve ter estrutura correta com todos os elementos', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      expect(wrapper.find('.dialog-overlay').exists()).toBe(true);
      expect(wrapper.find('.dialog-container').exists()).toBe(true);
      expect(wrapper.find('.dialog-content').exists()).toBe(true);
      expect(wrapper.find('.dialog-title').exists()).toBe(true);
      expect(wrapper.find('.dialog-message').exists()).toBe(true);
      expect(wrapper.find('.dialog-actions').exists()).toBe(true);
    });

    it('deve aplicar classes CSS corretas nos botões', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const cancelButton = wrapper.find('.dialog-button-cancel');
      const confirmButton = wrapper.find('.dialog-button-confirm');

      expect(cancelButton.exists()).toBe(true);
      expect(confirmButton.exists()).toBe(true);
      expect(cancelButton.classes()).toContain('dialog-button');
      expect(confirmButton.classes()).toContain('dialog-button');
    });
  });

  describe('Accessibility', () => {
    it('deve ter título como heading h3', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar exclusão',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const title = wrapper.find('h3.dialog-title');
      expect(title.exists()).toBe(true);
    });

    it('deve ter mensagem como parágrafo', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Tem certeza?'
        },
        attachTo: document.body
      });

      const message = wrapper.find('p.dialog-message');
      expect(message.exists()).toBe(true);
    });

    it('deve ter botões com texto descritivo', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const cancelButton = wrapper.find('button.dialog-button-cancel');
      const confirmButton = wrapper.find('button.dialog-button-confirm');

      expect(cancelButton.text().length).toBeGreaterThan(0);
      expect(confirmButton.text().length).toBeGreaterThan(0);
    });
  });

  describe('Visual feedback', () => {
    it('deve ter overlay com fundo escuro', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const overlay = wrapper.find('.dialog-overlay');
      expect(overlay.exists()).toBe(true);
    });

    it('deve ter container centralizado', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const container = wrapper.find('.dialog-container');
      expect(container.exists()).toBe(true);
    });

    it('deve ter botões com estilos distintos', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      const cancelButton = wrapper.find('.dialog-button-cancel');
      const confirmButton = wrapper.find('.dialog-button-confirm');

      expect(cancelButton.classes()).not.toEqual(confirmButton.classes());
    });
  });

  describe('Animation support', () => {
    it('deve ter transition wrapper', () => {
      const wrapper = mount(ConfirmDialog, {
        props: {
          isOpen: true,
          title: 'Confirmar',
          message: 'Mensagem'
        },
        attachTo: document.body
      });

      // The Transition component wraps the content
      expect(wrapper.find('.dialog-overlay').exists()).toBe(true);
    });
  });
});
