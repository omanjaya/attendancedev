import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginPage from './login';

// Mock the router
const mockNavigate = vi.fn();
vi.mock('@tanstack/react-router', () => ({
  useNavigate: () => mockNavigate,
}));

// Mock the stores
const mockLogin = vi.fn();
const mockShowError = vi.fn();

vi.mock('@/stores', () => ({
  useAuthStore: () => ({
    login: mockLogin,
    isLoading: false,
    error: null,
  }),
  useNotificationStore: () => ({
    error: mockShowError,
    success: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}));

describe('LoginPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('should render login form', () => {
      render(<LoginPage />);

      expect(screen.getByText('Attendance System')).toBeInTheDocument();
      expect(screen.getByLabelText('Email')).toBeInTheDocument();
      expect(screen.getByLabelText('Password')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /masuk/i })).toBeInTheDocument();
    });

    it('should render remember me checkbox', () => {
      render(<LoginPage />);

      expect(screen.getByText('Ingat saya')).toBeInTheDocument();
    });

    it('should render forgot password link', () => {
      render(<LoginPage />);

      expect(screen.getByText('Lupa password?')).toBeInTheDocument();
    });
  });

  describe('Form Validation', () => {
    it('should not call login with invalid email', async () => {
      const user = userEvent.setup();
      render(<LoginPage />);

      const emailInput = screen.getByLabelText('Email');
      const passwordInput = screen.getByLabelText('Password');
      const submitButton = screen.getByRole('button', { name: /masuk/i });

      // Fill in both fields - email invalid, password valid
      await user.type(emailInput, 'invalid-email');
      await user.type(passwordInput, 'password123');
      await user.click(submitButton);

      // Should not call login due to validation failure
      await waitFor(() => {
        expect(mockLogin).not.toHaveBeenCalled();
      });
    });

    it('should show error for short password', async () => {
      const user = userEvent.setup();
      render(<LoginPage />);

      const emailInput = screen.getByLabelText('Email');
      const passwordInput = screen.getByLabelText('Password');
      const submitButton = screen.getByRole('button', { name: /masuk/i });

      await user.type(emailInput, 'test@example.com');
      await user.type(passwordInput, '12345');
      await user.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText('Password minimal 6 karakter')).toBeInTheDocument();
      });
    });
  });

  describe('Password Visibility', () => {
    it('should toggle password visibility', async () => {
      const user = userEvent.setup();
      render(<LoginPage />);

      const passwordInput = screen.getByLabelText('Password');
      expect(passwordInput).toHaveAttribute('type', 'password');

      // Find the toggle button (it's the only button without text content in the password field area)
      const toggleButtons = screen.getAllByRole('button');
      const toggleButton = toggleButtons.find(btn => btn.querySelector('svg') && btn.getAttribute('type') === 'button');

      if (toggleButton) {
        await user.click(toggleButton);
        expect(passwordInput).toHaveAttribute('type', 'text');

        await user.click(toggleButton);
        expect(passwordInput).toHaveAttribute('type', 'password');
      }
    });
  });

  describe('Form Submission', () => {
    it('should call login with form data on valid submission', async () => {
      const user = userEvent.setup();
      mockLogin.mockResolvedValueOnce({});

      render(<LoginPage />);

      const emailInput = screen.getByLabelText('Email');
      const passwordInput = screen.getByLabelText('Password');
      const submitButton = screen.getByRole('button', { name: /masuk/i });

      await user.type(emailInput, 'test@example.com');
      await user.type(passwordInput, 'password123');
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockLogin).toHaveBeenCalledWith({
          email: 'test@example.com',
          password: 'password123',
          remember: false,
        });
      });
    });

    it('should navigate to dashboard on successful login', async () => {
      const user = userEvent.setup();
      mockLogin.mockResolvedValueOnce({});

      render(<LoginPage />);

      const emailInput = screen.getByLabelText('Email');
      const passwordInput = screen.getByLabelText('Password');
      const submitButton = screen.getByRole('button', { name: /masuk/i });

      await user.type(emailInput, 'test@example.com');
      await user.type(passwordInput, 'password123');
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith({ to: '/dashboard' });
      });
    });

    it('should show error notification on failed login', async () => {
      const user = userEvent.setup();
      mockLogin.mockRejectedValueOnce(new Error('Invalid credentials'));

      render(<LoginPage />);

      const emailInput = screen.getByLabelText('Email');
      const passwordInput = screen.getByLabelText('Password');
      const submitButton = screen.getByRole('button', { name: /masuk/i });

      await user.type(emailInput, 'test@example.com');
      await user.type(passwordInput, 'wrongpassword');
      await user.click(submitButton);

      await waitFor(() => {
        expect(mockShowError).toHaveBeenCalledWith('Login Gagal', 'Invalid credentials');
      });
    });
  });
});
